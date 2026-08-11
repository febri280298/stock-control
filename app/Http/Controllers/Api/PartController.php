<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Part;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\PartsImport;

class PartController extends Controller
{
    public function index()
    {
        return response()->json(Part::orderBy('model')->orderBy('commodity')->get());
    }

    public function store(Request $request)
    {
        $request->validate([
            'commodity'   => 'required|string',
            'part_name'   => 'required|string',
            'part_number' => 'required|string|unique:parts,part_number',
            'supplier'    => 'nullable|string',
            'stock'       => 'integer|min:0',
            'min_stock'   => 'integer|min:1',
        ]);

        $part = Part::create([
            'model'       => $request->model,
            'commodity'   => $request->commodity,
            'part_name'   => $request->part_name,
            'part_number' => $request->part_number,
            'supplier'    => $request->supplier,
            'stock'       => $request->stock ?? 0,
            'min_stock'   => $request->min_stock ?? 1,
        ]);

        return response()->json(['message' => 'Part ditambahkan', 'id' => $part->id], 201);
    }

    public function destroy($id)
    {
        $part = Part::findOrFail($id);
        $part->delete();
        return response()->json(['message' => 'Part dihapus']);
    }
    public function import(Request $request)
{
    $request->validate([
        'file' => 'required|mimes:xlsx,xls,csv',
    ]);

    $import = new PartsImport();

    try {
        Excel::import($import, $request->file('file'));
    } catch (\Exception $e) {
        return response()->json(['message' => 'Import gagal: ' . $e->getMessage()], 500);
    }

    $failures = $import->failures();

    if ($failures->count() > 0) {
        $errorMsgs = $failures->map(function ($f) {
            return 'Baris ' . $f->row() . ': ' . implode(', ', $f->errors());
        })->take(10)->implode(' | ');

        return response()->json([
            'message' => $failures->count() . ' baris gagal diimport (biasanya PN duplikat atau kolom wajib kosong). Contoh: ' . $errorMsgs,
        ], 422);
    }

    return response()->json(['message' => 'Import berhasil! Semua part berhasil ditambahkan.']);
    }
}
