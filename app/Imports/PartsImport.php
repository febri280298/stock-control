<?php

namespace App\Imports;

use App\Models\Part;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsFailures;

class PartsImport implements ToModel, WithHeadingRow, SkipsEmptyRows, WithValidation, SkipsOnFailure
{
    use SkipsFailures;

    public function model(array $row)
    {
        return new Part([
            'model'       => $row['model'] ?? null,
            'commodity'   => $row['commodity'],
            'part_name'   => $row['part_name'],
            'part_number' => $row['part_number'],
            'supplier'    => $row['supplier'] ?? null,
            'stock'       => $row['stok_awal'] ?? 0,
            'min_stock'   => $row['minimal_stok'] ?? 1,
        ]);
    }

    public function rules(): array
    {
        return [
            'commodity'   => 'required',
            'part_name'   => 'required',
            'part_number' => 'required|distinct|unique:parts,part_number',
        ];
    }
}