<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Part;
use Illuminate\Http\Request;

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
            'stock'       => 'integer|min:0',
            'min_stock'   => 'integer|min:1',
        ]);

        $part = Part::create([
            'model'       => $request->model,
            'commodity'   => $request->commodity,
            'part_name'   => $request->part_name,
            'part_number' => $request->part_number,
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
}
