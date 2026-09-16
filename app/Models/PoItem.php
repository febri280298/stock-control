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

    public function batches()
    {
        return $this->hasMany(PoItemBatch::class)->orderBy('batch_number');
    }

    // Add (or, with a negative qty, roll back) delivered qty.
    // Positive qty: fills the earliest incomplete batch first (FIFO), spilling over to the next batch if needed.
    // Negative qty (rollback): removes from the most recently filled batch first (reverse FIFO), symmetric with the above.
    public function addDelivery(int $qty)
    {
        if ($qty >= 0) {
            $remaining = $qty;
            foreach ($this->batches as $batch) {
                if ($remaining <= 0) break;
                $sisaBatch = $batch->qty - $batch->qty_delivered;
                if ($sisaBatch <= 0) continue;
                $alloc = min($sisaBatch, $remaining);
                $batch->qty_delivered += $alloc;
                $batch->save();
                $remaining -= $alloc;
            }
        } else {
            $toRemove = abs($qty);
            foreach ($this->batches()->orderBy('batch_number', 'desc')->get() as $batch) {
                if ($toRemove <= 0) break;
                $reduce = min($batch->qty_delivered, $toRemove);
                $batch->qty_delivered -= $reduce;
                $batch->save();
                $toRemove -= $reduce;
            }
        }

        $this->qty_delivered += $qty;

        $this->status = $this->qty_delivered >= $this->qty_order
            ? 'closed'
            : ($this->qty_delivered > 0 ? 'partial' : 'open');

        $this->save();
        $this->po->refreshStatus();
    }
}