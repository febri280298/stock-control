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

        // Update stok
        if ($request->type === 'masuk') {
            $part->increment('stock', $request->qty);
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

    public function destroy($id, Request $request)
    {
        // Hanya admin yang boleh hapus
        if (($request->user()->role ?? 'user') !== 'admin') {
            return response()->json(['message' => 'Tidak diizinkan'], 403);
        }

        $t = Transaction::with('part')->findOrFail($id);

        // Rollback stok
        if ($t->type === 'masuk') {
            $t->part->decrement('stock', $t->qty);
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
            $masuk  = Transaction::where('type', 'masuk')->where('date', $date)->sum('qty');
            $keluar = Transaction::where('type', 'keluar')->where('date', $date)->sum('qty');
            $data[] = ['date' => $date, 'masuk' => $masuk, 'keluar' => $keluar];
        }
        return response()->json($data);
    }
}
