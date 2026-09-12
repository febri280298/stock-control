<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Part;
use App\Models\Po;
use App\Models\PoItem;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PoController extends Controller
{
    // GET /po           -> semua PO (buat Rekap PO)
    // GET /po?status=open,partial -> hanya PO yang belum closed (buat dropdown di form Keluar)
    public function index(Request $request)
    {
        $query = Po::with('items', 'approvalMkt1User', 'approvalPcdUser', 'approvalMkt2User')->orderBy('po_date', 'desc');

        if ($request->status) {
            $statuses = explode(',', $request->status);
            $query->whereIn('status', $statuses);
        }

        return response()->json($query->get()->map(function ($po) {
            return [
                'id'              => $po->id,
                'po_number'       => $po->po_number,
                'po_date'         => $po->po_date,
                'customer_id'     => $po->customer_id,
                'target_delivery' => $po->target_delivery,
                'project'         => $po->project,
                'status'          => $po->status,
                'item_count'      => $po->items->count(),
                'items'           => $po->items->map(fn ($it) => [
                    'id'            => $it->id,
                    'part_number'   => $it->part->part_number ?? '',
                    'part_name'     => $it->part->part_name ?? '',
                    'qty_order'     => $it->qty_order,
                    'qty_delivered' => $it->qty_delivered,
                    'status'        => $it->status,
                ]),
            ] + $this->approvalPayload($po);
        }));
    }

    // GET /po/{id} -> detail 1 PO
    public function show($id)
    {
        $po = Po::with('items.part', 'approvalMkt1User', 'approvalPcdUser', 'approvalMkt2User')->findOrFail($id);

        return response()->json([
            'id'              => $po->id,
            'po_number'       => $po->po_number,
            'po_date'         => $po->po_date,
            'customer_id'     => $po->customer_id,
            'target_delivery' => $po->target_delivery,
            'project'         => $po->project,
            'status'          => $po->status,
            'items'           => $po->items->map(fn ($it) => [
                'id'            => $it->id,
                'part_number'   => $it->part->part_number ?? '',
                'part_name'     => $it->part->part_name ?? '',
                'qty_order'     => $it->qty_order,
                'qty_delivered' => $it->qty_delivered,
                'status'        => $it->status,
            ]),
        ] + $this->approvalPayload($po));
    }

    // Susunan data approval yang dipakai bareng di index() & show()
    private function approvalPayload(Po $po): array
    {
        return [
            'approval_stage'    => $po->approvalStage(),
            'approval_mkt1_by'  => $po->approvalMkt1User->name ?? null,
            'approval_mkt1_at'  => $po->approval_mkt1_at,
            'approval_pcd_by'   => $po->approvalPcdUser->name ?? null,
            'approval_pcd_at'   => $po->approval_pcd_at,
            'approval_mkt2_by'  => $po->approvalMkt2User->name ?? null,
            'approval_mkt2_at'  => $po->approval_mkt2_at,
        ];
    }

    // POST /po/{id}/approve -> approve tahap saat ini (mkt1 -> pcd -> mkt2), sesuai role user yang login
    public function approve($id, Request $request)
    {
        $po = Po::findOrFail($id);
        $role = $request->user()->role ?? 'user';
        $stage = $po->approvalStage();

        if ($stage === 'delivery') {
            return response()->json(['message' => 'PO ini belum Closed (delivery belum selesai), belum bisa diapprove.'], 422);
        }
        if ($stage === 'completed') {
            return response()->json(['message' => 'PO ini sudah selesai semua tahap approval-nya.'], 422);
        }

        $stepRoles = ['mkt1' => 'marketing', 'pcd' => 'pcd', 'mkt2' => 'marketing'];
        if (!in_array($role, [$stepRoles[$stage], 'admin'])) {
            return response()->json(['message' => 'Tidak diizinkan approve tahap ini.'], 403);
        }

        $po->{"approval_{$stage}_by"} = $request->user()->id;
        $po->{"approval_{$stage}_at"} = now();
        $po->save();

        $newStage = $po->approvalStage();
        $message = $newStage === 'completed' ? 'PO selesai! Semua tahap approval sudah lengkap.' : 'Approval berhasil, lanjut ke tahap berikutnya.';

        ActivityLogger::log($request, 'approve', 'Po', $po->id, "Approve tahap {$stage} untuk PO {$po->po_number}");

        return response()->json(['message' => $message, 'approval_stage' => $newStage]);
    }

    // POST /po -> bikin PO baru + items sekaligus (role dicek di middleware route)
    public function store(Request $request)
    {
        $request->validate([
            'po_number'            => 'required|string|unique:po,po_number',
            'po_date'              => 'required|date',
            'customer_id'          => 'nullable|string',
            'target_delivery'      => 'nullable|date',
            'project'              => 'nullable|string',
            'items'                => 'required|array|min:1',
            'items.*.part_number'  => 'required|string',
            'items.*.qty_order'    => 'required|integer|min:1',
        ]);

        $po = DB::transaction(function () use ($request) {
            $po = Po::create([
                'po_number'       => $request->po_number,
                'po_date'         => $request->po_date,
                'customer_id'     => $request->customer_id,
                'target_delivery' => $request->target_delivery,
                'project'         => $request->project,
                'created_by'      => $request->user()->id ?? null,
                'status'          => 'open',
            ]);

            foreach ($request->items as $item) {
                $part = Part::where('part_number', $item['part_number'])->firstOrFail();
                PoItem::create([
                    'po_id'     => $po->id,
                    'part_id'   => $part->id,
                    'qty_order' => $item['qty_order'],
                ]);
            }

            return $po;
        });

        ActivityLogger::log($request, 'create', 'Po', $po->id, "Membuat PO baru {$po->po_number} (" . count($request->items) . " item)");

        return response()->json(['message' => 'PO berhasil disimpan', 'id' => $po->id], 201);
    }

    // POST /po/{id}/items -> tambah 1 part baru ke PO yang udah ada
    // Cuma boleh selama PO belum closed (approval belum mulai sama sekali)
    public function addItem($id, Request $request)
    {
        $po = Po::findOrFail($id);

        if ($po->approvalStage() !== 'delivery') {
            return response()->json(['message' => 'PO ini sudah closed, part tidak bisa ditambah lagi.'], 422);
        }

        $request->validate([
            'part_number' => 'required|string',
            'qty_order'   => 'required|integer|min:1',
        ]);

        $part = Part::where('part_number', $request->part_number)->firstOrFail();

        $exists = $po->items()->where('part_id', $part->id)->exists();
        if ($exists) {
            return response()->json(['message' => 'Part ini sudah ada di PO ini.'], 422);
        }

        $item = PoItem::create([
            'po_id'     => $po->id,
            'part_id'   => $part->id,
            'qty_order' => $request->qty_order,
            'status'    => 'open',
        ]);

        $po->refreshStatus();

        ActivityLogger::log(
            $request, 'update', 'Po', $po->id,
            "Tambah part {$part->part_number} (qty {$request->qty_order}) ke PO {$po->po_number}"
        );

        return response()->json(['message' => 'Part berhasil ditambahkan', 'id' => $item->id], 201);
    }

    // DELETE /po/{id}/items/{itemId} -> hapus part dari PO
    // Cuma boleh kalau PO belum closed DAN part-nya belum ada pengiriman sama sekali
    public function removeItem($id, $itemId, Request $request)
    {
        $po = Po::findOrFail($id);
        $item = $po->items()->findOrFail($itemId);

        if ($po->approvalStage() !== 'delivery') {
            return response()->json(['message' => 'PO ini sudah closed, part tidak bisa dihapus lagi.'], 422);
        }

        if ($item->qty_delivered > 0) {
            return response()->json(['message' => 'Part ini sudah ada pengiriman, tidak bisa dihapus.'], 422);
        }

        $label = $item->part->part_number ?? ('#' . $item->id);
        $item->delete();

        $po->refreshStatus();

        ActivityLogger::log($request, 'update', 'Po', $po->id, "Hapus part {$label} dari PO {$po->po_number}");

        return response()->json(['message' => 'Part berhasil dihapus']);
    }
}