<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Part;
use App\Models\Transaction;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = Transaction::with('part', 'user')->orderBy('date', 'desc')->orderBy('id', 'desc');

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
            'part_number' => 'required|string',
            'type'        => 'required|in:masuk,keluar',
            'qty'         => 'required|integer|min:1',
            'date'        => 'required|date',
            'status_qc'   => 'required|string',
        ]);

        $part = Part::where('part_number', $request->part_number)->firstOrFail();

        if ($request->type === 'keluar' && $part->stock < $request->qty) {
            return response()->json(['message' => 'Stok tidak cukup!'], 422);
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
            'part_id'    => $part->id,
            'type'       => $request->type,
            'qty'        => $request->qty,
            'date'       => $request->date,
            'time'       => now()->format('H:i:s'),
            'status_qc'  => $request->status_qc,
            'keterangan' => $request->keterangan,
            'supplier'   => $request->supplier,
            'tujuan'     => $request->tujuan,
            'user_id'    => $request->user()->id,
        ]);

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
        
        return response()->json(['message' => 'Status berhasil diupdate']);
    }

    public function destroy($id, Request $request)
    {
        // Hanya admin yang boleh hapus
        if (($request->user()->role ?? 'user') !== 'admin') {
            return response()->json(['message' => 'Tidak diizinkan'], 403);
        }

        $t = Transaction::with('part')->findOrFail($id);

        // Rollback stok
        // Rollback stok
        if ($t->type === 'masuk') {
            // hanya rollback kalau transaksi ini pernah nambah stock (udah After Check QC)
            if ($t->status_qc === 'After Check QC') {
                $t->part->decrement('stock', $t->qty_ok ?? $t->qty);
            }
        } else {
            $t->part->increment('stock', $t->qty);
        }

        $t->delete();
        return response()->json(['message' => 'Transaksi dihapus']);
    }

    public function chart()
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $masuk  = Transaction::where('type', 'masuk')->where('date', $date)->where('status_qc', 'After Check QC')->sum('qty');
            $keluar = Transaction::where('type', 'keluar')->where('date', $date)->sum('qty');
            $data[] = ['date' => $date, 'masuk' => $masuk, 'keluar' => $keluar];
        }
        return response()->json($data);
    }
}
