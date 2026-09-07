<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = [
        'part_id', 'type', 'qty', 'date', 'time',
        'status_qc', 'keterangan', 'supplier', 'tujuan', 'user_id',
        'kategori_keluar', 'po_item_id', 'keterangan_non_po',
        'qty_ok', 'keterangan_reject',
    ];

    public function part()
    {
        return $this->belongsTo(Part::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function poItem()
    {
        return $this->belongsTo(\App\Models\PoItem::class);
    }
}