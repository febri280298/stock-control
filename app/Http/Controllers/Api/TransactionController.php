<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Part;
use App\Models\PoItem;
use App\Models\Transaction;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = Transaction::with('part', 'user', 'poItem.po')->orderBy('date', 'desc')->orderBy('id', 'desc');

        if ($request->type) {
            $query->where('type', $request->type);
        }
        if ($request->search) {
            $query->whereHas('part', function ($q) use ($request) {
                $q->where('part_number', 'like', '%' . $request->search . '%')
                  ->orWhere('part_name', 'like', '%' . $request->search . '%');
            });
        }

        return response()->json($query->get()->map(function ($t) {
            return [
                'id'         => $t->id,
                'type'       => $t->type,
                'qty'        => $t->qty,
                'qty_ok'     => $t->qty_ok ?? $t->qty,
                'date'       => $t->date,
                'time'       => $t->time,
                'status_qc'  => $t->status_qc,
                'keterangan' => $t->keterangan,
                'supplier'   => $t->supplier,
                'tujuan'     => $t->tujuan,
                'kategori_keluar'    => $t->kategori_keluar,
                'po_number'          => $t->poItem->po->po_number ?? null,
                'keterangan_non_po'  => $t->keterangan_non_po,
                'part_number' => $t->part->part_number ?? '',
                'part_name'   => $t->part->part_name ?? '',
                'commodity'   => $t->part->commodity ?? '',
                'model'       => $t->part->model ?? '',
                'input_by'    => $t->user->name ?? '-',
            ];
        }));
    }

    public function store(Request $request)
    {
        $request->validate([
            'part_number'        => 'required|string',
            'type'               => 'required|in:masuk,keluar',
            'qty'                => 'required|integer|min:1',
            'date'               => 'required|date',
            'status_qc'          => 'required|string',
            'kategori_keluar'    => 'required_if:type,keluar|in:po,non_po',
            'po_item_id'         => 'required_if:kategori_keluar,po|nullable|exists:po_items,id',
            'keterangan_non_po'  => 'required_if:kategori_keluar,non_po|nullable|string',
        ]);

        $part = Part::where('part_number', $request->part_number)->firstOrFail();

        if ($request->type === 'keluar' && $part->stock < $request->qty) {
            return response()->json(['message' => 'Stok tidak cukup!'], 422);
        }

        $poItem = null;
        if ($request->type === 'keluar' && $request->kategori_keluar === 'po') {
            $poItem = PoItem::findOrFail($request->po_item_id);

            if ($poItem->part_id !== $part->id) {
                return response()->json(['message' => 'Part number tidak sesuai dengan item PO yang dipilih!'], 422);
            }

            $sisa = $poItem->qty_order - $poItem->qty_delivered;
            if ($request->qty > $sisa) {
                return response()->json(['message' => "Qty keluar melebihi sisa PO (sisa: {$sisa})"], 422);
            }
        }

        // Update stok - kalau masuk & masih Before Check QC, stok belum nambah (nunggu di-approve)
        if ($request->type === 'masuk') {
            if ($request->status_qc === 'After Check QC') {
                $part->increment('stock', $request->qty);
            }
        } else {
            $part->decrement('stock', $request->qty);
        }

        $transaction = Transaction::create([
            'part_id'           => $part->id,
            'type'              => $request->type,
            'qty'               => $request->qty,
            'date'              => $request->date,
            'time'              => now()->format('H:i:s'),
            'status_qc'         => $request->status_qc,
            'keterangan'        => $request->keterangan,
            'supplier'          => $request->supplier,
            'tujuan'            => $request->tujuan,
            'kategori_keluar'   => $request->kategori_keluar,
            'po_item_id'        => $poItem->id ?? null,
            'keterangan_non_po' => $request->keterangan_non_po,
            'user_id'           => $request->user()->id,
        ]);

        if ($poItem) {
            $poItem->addDelivery($request->qty);
        }

        $label = $request->type === 'masuk' ? 'Barang masuk' : 'Barang keluar';
        ActivityLogger::log(
            $request, 'create', 'Transaction', $transaction->id,
            "{$label}: {$part->part_number} ({$part->part_name}) qty {$request->qty}"
        );

        return response()->json(['message' => 'Tersimpan!', 'id' => $transaction->id], 201);
    }

    public function approveQc(Request $request, $id)
    {
        $request->validate([
            'qty_ok'            => 'required|integer|min:0',
            'keterangan_reject' => 'nullable|string',
        ]);

        $transaction = Transaction::with('part')->findOrFail($id);

        if ($transaction->status_qc === 'After Check QC') {
            return response()->json(['message' => 'Transaksi ini sudah After Check QC'], 422);
        }

        if ($request->qty_ok > $transaction->qty) {
            return response()->json(['message' => 'Qty OK tidak boleh lebih besar dari qty awal'], 422);
        }

        $transaction->update([
            'status_qc'         => 'After Check QC',
            'qty_ok'            => $request->qty_ok,
            'keterangan_reject' => $request->keterangan_reject,
        ]);

        if ($transaction->type === 'masuk' && $request->qty_ok > 0) {
            $transaction->part->increment('stock', $request->qty_ok);
        }

        $rejectQty = $transaction->qty - $request->qty_ok;
        if ($rejectQty > 0) {
            $transaction->part->increment('total_reject', $rejectQty);
        }

        ActivityLogger::log(
            $request, 'approve', 'Transaction', $transaction->id,
            "QC approve {$transaction->part->part_number}: OK {$request->qty_ok} / Reject {$rejectQty} (dari total {$transaction->qty})"
        );

        return response()->json(['message' => 'Status berhasil diupdate']);
    }

    // Role admin/pcd dicek di middleware route
    public function destroy($id, Request $request)
    {
        $t = Transaction::with('part', 'poItem')->findOrFail($id);
        $label = "{$t->type} {$t->part->part_number} qty {$t->qty}";

        // Rollback stok
        if ($t->type === 'masuk') {
            // hanya rollback kalau transaksi ini pernah nambah stock (udah After Check QC)
            if ($t->status_qc === 'After Check QC') {
                $t->part->decrement('stock', $t->qty_ok ?? $t->qty);
            }
        } else {
            $t->part->increment('stock', $t->qty);
        }

        // Rollback qty_delivered di PO item kalau transaksi ini terikat PO
        if ($t->poItem) {
            $t->poItem->addDelivery(-$t->qty);
        }

        $t->delete();

        ActivityLogger::log($request, 'delete', 'Transaction', $id, "Menghapus transaksi {$label}");

        return response()->json(['message' => 'Transaksi dihapus']);
    }

    public function chart()
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            // qty_ok hanya terisi lewat approveQc(). Transaksi yang langsung
            // diinput sebagai "After Check QC" meninggalkan qty_ok NULL, jadi
            // harus jatuh ke qty - kalau tidak, batang hijaunya hilang dari grafik.
            $masuk  = Transaction::where('type', 'masuk')->where('date', $date)
                ->where('status_qc', 'After Check QC')
                ->sum(DB::raw('COALESCE(qty_ok, qty)'));
            $keluar = Transaction::where('type', 'keluar')->where('date', $date)->sum('qty');
            $data[] = ['date' => $date, 'masuk' => $masuk, 'keluar' => $keluar];
        }
        return response()->json($data);
    }
}
