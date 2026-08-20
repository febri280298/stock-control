<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class SuratJalanController extends Controller
{
    public function generate(Request $request)
    {
        $request->validate([
            'ids'         => 'required|array|min:1',
            'delivery_to' => 'nullable|string',
            'date'        => 'nullable|string',
            'no'          => 'nullable|string',
            'project'     => 'nullable|string',
            'no_po'       => 'nullable|string',
        ]);

        $items = Transaction::with('part')
            ->whereIn('id', $request->ids)
            ->orderBy('id')
            ->get();

        // Form FM-PCD-002 rev.00 digambar ulang di resources/views/pdf/surat-jalan.blade.php
        // (ukuran Letter 612x792 pt, koordinat mengikuti form aslinya).
        $pdf = Pdf::loadView('pdf.surat-jalan', [
            'items'       => $items,
            'delivery_to' => $request->delivery_to ?: '',
            'date'        => $request->date
                                ? date('d-m-Y', strtotime($request->date))
                                : now()->format('d-m-Y'),
            'no'          => $request->no ?: '',
            'project'     => $request->project ?: '',
            'no_po'       => $request->no_po ?: '',
        ])->setPaper('letter', 'portrait');

        $filename = 'surat-jalan-' . now()->format('Ymd-His') . '.pdf';

        return response($pdf->output(), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }
}
