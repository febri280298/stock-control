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
     * Cari ukuran font terbesar yang membuat $text muat dalam $lines baris
     * selebar $maxWidth pt. Kalau di ukuran terkecil pun masih lewat, teksnya
     * dipotong dan diberi elipsis.
     *
     * @return array{0:string,1:float} [teks siap cetak, ukuran font dalam pt]
     */
    public static function fit(
        string $text,
        float $maxWidth,
        int $lines = 2,
        float $max = 8.5,
        float $min = 5.5,
        float $step = 0.5
    ): array {
        $text = trim($text);

        if ($text === '') {
            return ['', $max];
        }

        $budget = $maxWidth * ($lines > 1 ? $lines * self::FILL : 1);

        for ($size = $max; $size >= $min; $size -= $step) {
            if (self::fits($text, $size, $budget, $maxWidth)) {
                return [$text, $size];
            }
        }

        $cut = $text;
        while ($cut !== '' && ! self::fits($cut . '...', $min, $budget, $maxWidth)) {
            $cut = mb_substr($cut, 0, mb_strlen($cut) - 1);
        }

        return [rtrim($cut) . '...', $min];
    }

    /**
     * Muat bila dua syarat terpenuhi: total teks masuk dalam jatah baris, DAN
     * kata terpanjang tidak melebihi satu kolom. Syarat kedua penting karena
     * kata yang tidak bisa dipenggal akan memaksa kolom melar.
     */
    private static function fits(string $text, float $size, float $budget, float $maxWidth): bool
    {
        if (self::width($text, $size) > $budget) {
            return false;
        }

        foreach (preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY) as $word) {
            if (self::width($word, $size) > $maxWidth) {
                return false;
            }
        }

        return true;
    }
}
