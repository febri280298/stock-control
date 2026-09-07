<?php

namespace App\Http\Controllers;

use App\Models\SuratJalan;
use App\Models\Transaction;
use App\Services\ActivityLogger;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SuratJalanController extends Controller
{
    public function generate(Request $request)
    {
        $request->validate([
            'ids'             => 'required|array|min:1',
            'delivery_to'     => 'nullable|string',
            'date'            => 'nullable|string',
            'project'         => 'nullable|string',
            'no_po'           => 'nullable|string',
            'no_surat_jalan'  => 'nullable|string',
        ]);

        $items = Transaction::with('part')
            ->whereIn('id', $request->ids)
            ->orderBy('id')
            ->get();

        $date = $request->date
            ? date('d-m-Y', strtotime($request->date))
            : now()->format('d-m-Y');

        // Form FM-PCD-002 digambar ulang di resources/views/pdf/surat-jalan.blade.php,
        // jadi tidak lagi bergantung pada file template PDF. Pengepasan teks panjang
        // ditangani App\Support\PdfText memakai metrik font asli.
        $pdfContent = Pdf::loadView('pdf.surat-jalan', [
            'items'       => $items,
            'delivery_to' => $request->delivery_to ?: '',
            'date'        => $date,
            'no'          => $request->no_surat_jalan ?: '',
            'project'     => $request->project ?: '',
            'no_po'       => $request->no_po ?: '',
        ])->setPaper('letter', 'portrait')->output();

        $filename = 'surat-jalan-' . now()->format('Ymd-His') . '.pdf';

        // Simpan file PDF-nya di server, biar bisa diunduh ulang nanti tanpa perlu
        // generate ulang (dan isinya tetap persis sama seperti saat pertama dicetak).
        $storedPath = 'surat_jalan/' . $filename;
        Storage::disk('local')->put($storedPath, $pdfContent);

        $suratJalan = SuratJalan::create([
            'no_surat_jalan'  => $request->no_surat_jalan,
            'delivery_to'     => $request->delivery_to,
            'date'            => $request->date ?: now()->format('Y-m-d'),
            'project'         => $request->project,
            'no_po'           => $request->no_po,
            'transaction_ids' => $request->ids,
            'file_path'       => $storedPath,
            'user_id'         => $request->user()->id,
            'user_name'       => $request->user()->name ?? '-',
        ]);

        ActivityLogger::log(
            $request, 'create', 'SuratJalan', $suratJalan->id,
            "Generate Surat Jalan {$request->no_surat_jalan} untuk {$request->delivery_to} (" . count($request->ids) . ' item)'
        );

        return response($pdfContent, 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    // GET /surat-jalan -> daftar semua surat jalan yang pernah di-generate
    public function index()
    {
        return response()->json(
            SuratJalan::with('user')->orderBy('created_at', 'desc')->get()->map(function ($sj) {
                return [
                    'id'             => $sj->id,
                    'no_surat_jalan' => $sj->no_surat_jalan,
                    'delivery_to'    => $sj->delivery_to,
                    'date'           => $sj->date,
                    'project'        => $sj->project,
                    'no_po'          => $sj->no_po,
                    'item_count'     => count($sj->transaction_ids ?? []),
                    'created_by'     => $sj->user->name ?? $sj->user_name ?? '-',
                    'created_at'     => $sj->created_at,
                ];
            })
        );
    }

    // GET /surat-jalan/{id}/download -> download ulang PDF yang sudah pernah dibuat
    public function download($id)
    {
        $sj = SuratJalan::findOrFail($id);

        if (!Storage::disk('local')->exists($sj->file_path)) {
            return response()->json(['message' => 'File PDF sudah tidak ada di server'], 404);
        }

        $filename = basename($sj->file_path);

        return response(Storage::disk('local')->get($sj->file_path), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }
}
