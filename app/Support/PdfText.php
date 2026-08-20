<?php

namespace App\Support;

use Dompdf\Dompdf;
use Dompdf\FontMetrics;

/**
 * Bantuan pengepasan teks untuk PDF berukuran tetap (surat jalan).
 *
 * Tinggi baris pada form surat jalan sudah dipatok, jadi teks panjang tidak
 * boleh meluber. Kelas ini mengukur lebar teks memakai metrik font asli dari
 * dompdf, lalu mengecilkan ukuran font secukupnya - dan hanya memotong teks
 * sebagai upaya terakhir.
 */
class PdfText
{
    private static ?FontMetrics $metrics = null;
    private static $font = null;

    /** Sebagian lebar baris terbuang karena pemenggalan hanya terjadi di spasi. */
    private const FILL = 0.92;

    private static function boot(): void
    {
        if (self::$metrics === null) {
            self::$metrics = (new Dompdf())->getFontMetrics();
            self::$font    = self::$metrics->getFont('Helvetica', 'normal');
        }
    }

    public static function width(string $text, float $size): float
    {
        self::boot();

        return self::$metrics->getTextWidth($text, self::$font, $size);
    }

    /**
     * Ukuran font terbesar (<= $max) yang membuat setiap KATA muat dalam satu
     * kolom selebar $maxWidth. Ini bukan soal estetika: kata yang lebih lebar
     * dari kolomnya akan memaksa kolom melar dan merusak tabel. Teks hanya
     * dipotong kalau di ukuran terkecil pun masih ada kata yang kelewat lebar.
     *
     * @return array{0:string,1:float} [teks siap cetak, ukuran font dalam pt]
     */
    public static function fitWords(
        string $text,
        float $maxWidth,
        float $max = 8.5,
        float $min = 5.5,
        float $step = 0.5
    ): array {
        $text = trim($text);

        if ($text === '') {
            return ['', $max];
        }

        for ($size = $max; $size >= $min; $size -= $step) {
            if (self::widestWord($text, $size) <= $maxWidth) {
                return [$text, $size];
            }
        }

        $cut = $text;
        while ($cut !== '' && self::widestWord($cut . '...', $min) > $maxWidth) {
            $cut = mb_substr($cut, 0, mb_strlen($cut) - 1);
        }

        return [rtrim($cut) . '...', $min];
    }

    /**
     * Jumlah baris yang dibutuhkan teks pada kolom selebar $maxWidth,
     * meniru pemenggalan per kata seperti yang dilakukan dompdf.
     */
    public static function lines(string $text, float $maxWidth, float $size): int
    {
        $words = preg_split('/\s+/', trim($text), -1, PREG_SPLIT_NO_EMPTY);

        if (! $words) {
            return 1;
        }

        $lines = 1;
        $cur   = '';

        foreach ($words as $w) {
            $candidate = $cur === '' ? $w : $cur . ' ' . $w;

            if (self::width($candidate, $size) <= $maxWidth) {
                $cur = $candidate;
                continue;
            }

            $lines++;
            $cur = $w;
        }

        return $lines;
    }

    private static function widestWord(string $text, float $size): float
    {
        $widest = 0.0;

        foreach (preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY) as $w) {
            $widest = max($widest, self::width($w, $size));
        }

        return $widest;
    }
}
