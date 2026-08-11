<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use setasign\Fpdi\Fpdi;

class SuratJalanController extends Controller
{
    public function generate(Request $request)
    {
        $request->validate([
            'ids'         => 'required|array|min:1',
            'delivery_to' => 'nullable|string',
            'date'        => 'nullable|string',
        ]);

        $items = Transaction::with('part')
            ->whereIn('id', $request->ids)
            ->orderBy('id')
            ->get();

        $templatePath = storage_path('app/templates/surat-jalan-template.pdf');

        // Ukuran halaman PDF asli: 612 x 792 pt (Letter)
        $pdf = new Fpdi('P', 'pt', [612, 792]);
        $pdf->AddPage();
        $pdf->setSourceFile($templatePath);
        $tpl = $pdf->importPage(1);
        $pdf->useTemplate($tpl, 0, 0, 612, 792);

        $pdf->SetFont('Helvetica', '', 9);
        $pdf->SetTextColor(0, 0, 0);

        // ===== DELIVERY TO & DATE =====
        $pdf->SetXY(138, 164);
        $pdf->Cell(140, 12, $request->delivery_to ?: '-');

        $pdf->SetXY(138, 176);
        $pdf->Cell(140, 12, $request->date ? date('d-m-Y', strtotime($request->date)) : now()->format('d-m-Y'));

        // ===== TABEL ITEM =====
        // Ubah 2 angka ini kalau posisi baris pertama / jarak antar baris kurang pas
        $rowY      = 220;   // posisi Y baris pertama
        $rowHeight = 20;    // jarak antar baris

        foreach ($items as $i => $item) {
            $pdf->SetXY(29, $rowY);
            $pdf->Cell(29.2, $rowHeight, (string) ($i + 1), 0, 0, 'C');

            $pdf->SetXY(61, $rowY);
            $pdf->Cell(155, $rowHeight, $item->part->part_name ?? '-');

            $pdf->SetXY(220, $rowY);
            $pdf->Cell(130, $rowHeight, $item->part->part_number ?? '-');

            // UNIQ dikosongin

            $pdf->SetXY(399, $rowY);
            $pdf->Cell(48, $rowHeight, (string) $item->qty, 0, 0, 'C');

            $pdf->SetXY(447, $rowY);
            $pdf->Cell(48, $rowHeight, 'PCS', 0, 0, 'C');

            $pdf->SetXY(498, $rowY);
            $ket = $item->type === 'masuk' ? ($item->supplier ?? '-') : ($item->tujuan ?? '-');
            $pdf->Cell(45, $rowHeight, $ket);

            $rowY += $rowHeight;
        }

        $filename = 'surat-jalan-' . now()->format('Ymd-His') . '.pdf';

        return response($pdf->Output('S', $filename), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }
}