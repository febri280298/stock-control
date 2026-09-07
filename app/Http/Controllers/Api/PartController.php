<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Part;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\PartsImport;

class PartController extends Controller
{
    // Role sudah dicek di routes/api.php lewat middleware 'role:...',
    // jadi controller ini fokus ke logic aja, ga perlu cek ulang di sini.

    public function index(Request $request)
    {
        $parts = Part::orderBy('model')->orderBy('commodity')->get();
        $canSeePrice = in_array($request->user()->role ?? 'user', ['admin', 'marketing']);

        if (!$canSeePrice) {
            $parts->makeHidden(['price', 'price_valid_from', 'price_valid_until', 'tarikan_sales']);
        }

        return response()->json($parts);
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

        ActivityLogger::log($request, 'create', 'Part', $part->id, "Menambahkan part baru {$part->part_number} - {$part->part_name}");

        return response()->json(['message' => 'Part ditambahkan', 'id' => $part->id], 201);
    }

    public function destroy(Request $request, $id)
    {
        $part = Part::findOrFail($id);
        $label = "{$part->part_number} - {$part->part_name}";
        $part->delete();

        ActivityLogger::log($request, 'delete', 'Part', $id, "Menghapus part {$label}");

        return response()->json(['message' => 'Part dihapus']);
    }

    // PUT /parts/{id}/price -> update harga part (role dicek di middleware route)
    public function updatePrice(Request $request, $id)
    {
        $part = Part::findOrFail($id);

        $request->validate([
            'price'              => 'required|numeric|min:0',
            'price_valid_from'   => 'required|date',
            'price_valid_until'  => 'required|date|after_or_equal:price_valid_from',
            'tarikan_sales'      => 'nullable|numeric|min:0',
        ]);

        $before = $part->only(['price', 'price_valid_from', 'price_valid_until', 'tarikan_sales']);

        $part->update([
            'price'              => $request->price,
            'price_valid_from'   => $request->price_valid_from,
            'price_valid_until'  => $request->price_valid_until,
            'tarikan_sales'      => $request->tarikan_sales,
        ]);

        ActivityLogger::log(
            $request, 'update_price', 'Part', $part->id,
            "Update price part {$part->part_number} jadi Rp" . number_format($request->price, 0, ',', '.'),
            ['before' => $before, 'after' => $part->only(['price', 'price_valid_from', 'price_valid_until', 'tarikan_sales'])]
        );

        return response()->json(['message' => 'Price berhasil diupdate', 'part' => $part]);
    }

    // POST /parts/import-price -> bulk update harga dari Excel (dicocokkan by part_number)
    public function importPrice(Request $request)
    {
        $request->validate([
            'items' => 'required|array|min:1',
        ]);

        $updated = 0;
        $notFound = [];
        $invalid = [];
        $updatedPns = [];

        foreach ($request->items as $item) {
            $pn = $item['part_number'] ?? null;
            if (!$pn) {
                continue;
            }

            $validator = \Illuminate\Support\Facades\Validator::make($item, [
                'part_number'        => 'required|string',
                'price'              => 'required|numeric|min:0',
                'price_valid_from'   => 'required|date',
                'price_valid_until'  => 'required|date|after_or_equal:price_valid_from',
                'tarikan_sales'      => 'nullable|numeric|min:0',
            ]);

            if ($validator->fails()) {
                $invalid[] = $pn . ' (' . implode('; ', $validator->errors()->all()) . ')';
                continue;
            }

            $part = Part::where('part_number', $pn)->first();
            if (!$part) {
                $notFound[] = $pn;
                continue;
            }

            $part->update([
                'price'             => $item['price'],
                'price_valid_from'  => $item['price_valid_from'],
                'price_valid_until' => $item['price_valid_until'],
                'tarikan_sales'     => $item['tarikan_sales'] ?? null,
            ]);
            $updated++;
            $updatedPns[] = $pn;
        }

        $message = $updated . ' part berhasil diupdate harganya.';
        if ($notFound) {
            $shown = array_slice($notFound, 0, 10);
            $message .= ' | Part number tidak ditemukan: ' . implode(', ', $shown) . (count($notFound) > 10 ? ' ...' : '');
        }
        if ($invalid) {
            $shownInvalid = array_slice($invalid, 0, 10);
            $message .= ' | Data tidak valid (dilewati): ' . implode(', ', $shownInvalid) . (count($invalid) > 10 ? ' ...' : '');
        }

        if ($updated > 0) {
            ActivityLogger::log(
                $request, 'import_price', 'Part', null,
                "Bulk import price ({$updated} part terupdate)",
                ['part_numbers' => $updatedPns, 'not_found' => $notFound, 'invalid' => $invalid]
            );
        }

        return response()->json(['message' => $message, 'updated' => $updated, 'not_found' => $notFound, 'invalid' => $invalid]);
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

        ActivityLogger::log($request, 'import', 'Part', null, 'Import part baru dari Excel');

        return response()->json(['message' => 'Import berhasil! Semua part berhasil ditambahkan.']);
    }
}