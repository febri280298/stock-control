<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PoItemBatch extends Model
{
    protected $fillable = ['po_item_id', 'batch_number', 'qty', 'qty_delivered', 'target_date'];

    public function item()
    {
        return $this->belongsTo(PoItem::class, 'po_item_id');
    }

    // Status batch ini, dihitung on-the-fly (bukan disimpen manual) — sesuai keputusan: cuma label visual.
    public function getStatusAttribute(): string
    {
        if ($this->qty_delivered >= $this->qty) return 'closed';
        if ($this->qty_delivered > 0) return 'partial';
        return 'open';
    }
}
