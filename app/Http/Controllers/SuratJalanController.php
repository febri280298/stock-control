<?php

namespace App\Http\Controllers;

use App\Models\Po;
use App\Models\SuratJalan;
use App\Models\Transaction;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\Fpdi;

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

        $templatePath = storage_path('app/templates/surat-jalan-template.pdf');

        // Ukuran halaman PDF asli: 612 x 792 pt (Letter)
        $pdf = new Fpdi('P', 'pt', [612, 792]);
        $pdf->AddPage();
        $pdf->setSourceFile($templatePath);
        $tpl = $pdf->importPage(1);
        $pdf->useTemplate($tpl, 0, 0, 612, 792);

        $pdf->SetFont('Helvetica', '', 9);
        $pdf->SetTextColor(0, 0, 0);

        // ===== NOMOR SURAT JALAN =====
        // Posisi udah pas masuk kotak "NO :" - tweak halus biar center & ga mepet kanan.
        $pdf->SetFont('Helvetica', 'B', 12);
        $pdf->SetXY(300, 113);
        $pdf->Cell(150, 16, $request->no_surat_jalan ?: '-');
        $pdf->SetFont('Helvetica', '', 9);

        // ===== DELIVERY TO & DATE =====
        $pdf->SetXY(150, 164);
        $pdf->Cell(140, 12, $request->delivery_to ?: '-');

        $pdf->SetXY(150, 176);
        $pdf->Cell(140, 12, $request->date ? date('d-m-Y', strtotime($request->date)) : now()->format('d-m-Y'));

        // ===== PROJECT & NO. PO (kanan) =====
        $pdf->SetXY(440, 164);
        $pdf->Cell(120, 12, $request->project ?: '-');

        $pdf->SetXY(440, 176);
        $pdf->Cell(120, 12, $request->no_po ?: '-');

        // ===== TABEL ITEM =====
        $rowY          = 220;   // posisi Y baris pertama
        $rowHeight     = 20;    // tinggi minimal 1 baris (kalau part name pendek)
        $partNameWidth = 155;   // lebar kolom PART NAME (sesuaikan sama garis kolom di template)
        $lineHeight    = 11;    // tinggi per baris teks kalau part name di-wrap jadi 2+ baris

        foreach ($items as $i => $item) {
            $partName = $item->part->part_name ?? '-';

            // Hitung berapa baris yang dibutuhin part name ini di lebar kolom yang ada,
            // biar baris berikutnya (item selanjutnya) turun secukupnya - ga numpuk/offside lagi.
            $pdf->SetFont('Helvetica', '', 9);
            $wrappedLines  = $this->wrapText($pdf, $partName, $partNameWidth);
            $thisRowHeight = max($rowHeight, count($wrappedLines) * $lineHeight + 4);

            $pdf->SetFont('Helvetica', '', 9);
            $pdf->SetXY(52, $rowY);
            $pdf->Cell(29.2, $rowHeight, (string) ($i + 1), 0, 0, 'C');

            // PART NAME - pake MultiCell biar otomatis turun ke baris baru kalau kepanjangan,
            // bukan numpuk/kepotong ke kolom sebelah (ini yang benerin bug "offside")
            $pdf->SetFont('Helvetica', '', 9);
            $pdf->SetXY(80, $rowY);
            $pdf->MultiCell($partNameWidth, $lineHeight, $partName);

            $pdf->SetXY(295, $rowY);
            $pdf->Cell(130, $rowHeight, $item->part->part_number ?? '-');
            $pdf->SetFont('Helvetica', '', 9);

            $pdf->SetXY(410, $rowY);
            $pdf->Cell(48, $rowHeight, (string) $item->qty, 0, 0, 'C');

            $pdf->SetXY(447, $rowY);
            $pdf->Cell(48, $rowHeight, 'PCS', 0, 0, 'C');

            $pdf->SetXY(498, $rowY);
            $ket = $item->type === 'masuk' ? ($item->supplier ?? '-') : ($item->tujuan ?? '-');
            $pdf->Cell(45, $rowHeight, $ket);

            $rowY += $thisRowHeight;
        }

        $filename = 'surat-jalan-' . now()->format('Ymd-His') . '.pdf';
        $pdfContent = $pdf->Output('S', $filename);

        // Simpen file PDF-nya beneran di server, biar bisa didownload ulang nanti
        // tanpa perlu generate ulang (dan datanya tetep persis sama kayak yang pertama dicetak)
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

    // GET /surat-jalan/{id}/download -> download ulang PDF yang udah pernah dibuat
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

    // GET /po/{id}/pending-surat-jalan -> transaksi keluar buat PO ini yang belum pernah
    // dibikinin surat jalan sama sekali (dipake tombol "Buat Surat Jalan" di Rekap PO)
    public function pendingForPo($poId)
    {
        $po = Po::with('items')->findOrFail($poId);
        $poItemIds = $po->items->pluck('id');

        $transactionIds = Transaction::whereIn('po_item_id', $poItemIds)
            ->where('type', 'keluar')
            ->pluck('id');

        // Kumpulin semua id transaksi yang udah pernah kepake di surat jalan manapun
        $usedIds = SuratJalan::pluck('transaction_ids')
            ->filter()
            ->flatten()
            ->unique()
            ->values();

        $pendingIds = $transactionIds->diff($usedIds)->values();

        return response()->json([
            'po_number'       => $po->po_number,
            'transaction_ids' => $pendingIds,
            'count'           => $pendingIds->count(),
        ]);
    }


    // Bantu hitung berapa baris yang dibutuhin sebuah teks kalau di-wrap di lebar tertentu,
    // biar kita bisa nentuin tinggi baris berikutnya sebelum benar-benar digambar.
    private function wrapText(Fpdi $pdf, string $text, float $maxWidth): array
    {
        $words = explode(' ', $text);
        $lines = [];
        $current = '';

        foreach ($words as $word) {
            $test = $current === '' ? $word : $current . ' ' . $word;
            if ($pdf->GetStringWidth($test) > $maxWidth && $current !== '') {
                $lines[] = $current;
                $current = $word;
            } else {
                $current = $test;
            }
        }
        if ($current !== '') {
            $lines[] = $current;
        }

        return $lines ?: [''];
    }
}