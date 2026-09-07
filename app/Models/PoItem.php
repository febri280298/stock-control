<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PoItem extends Model
{
    protected $fillable = ['po_id', 'part_id', 'qty_order', 'qty_delivered', 'status'];

    public function po()
    {
        return $this->belongsTo(Po::class, 'po_id');
    }

    public function part()
    {
        return $this->belongsTo(Part::class);
    }

    // Add delivered qty, recompute this item's status, then bubble up to the PO header.
    public function addDelivery(int $qty)
    {
        $this->qty_delivered += $qty;

        $this->status = $this->qty_delivered >= $this->qty_order
            ? 'closed'
            : ($this->qty_delivered > 0 ? 'partial' : 'open');

        $this->save();
        $this->po->refreshStatus();
    }
}
