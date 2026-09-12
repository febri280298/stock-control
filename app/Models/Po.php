<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Po extends Model
{
    protected $table = 'po';

    protected $fillable = ['po_number', 'po_date', 'customer_id', 'target_delivery', 'project', 'status', 'created_by', 'notes'];

    public function items()
    {
        return $this->hasMany(PoItem::class, 'po_id');
    }

    public function approvalMkt1User() { return $this->belongsTo(User::class, 'approval_mkt1_by'); }
    public function approvalPcdUser() { return $this->belongsTo(User::class, 'approval_pcd_by'); }
    public function approvalMkt2User() { return $this->belongsTo(User::class, 'approval_mkt2_by'); }

    // Tahap approval saat ini: delivery (belum closed), mkt1, pcd, mkt2, atau completed.
    public function approvalStage(): string
    {
        if ($this->status !== 'closed') return 'delivery';
        if (!$this->approval_mkt1_at) return 'mkt1';
        if (!$this->approval_pcd_at) return 'pcd';
        if (!$this->approval_mkt2_at) return 'mkt2';
        return 'completed';
    }

    // Recalculate this PO's overall status from its items, then save.
    public function refreshStatus()
    {
        $statuses = $this->items()->pluck('status');

        if ($statuses->every(fn ($s) => $s === 'closed')) {
            $this->status = 'closed';
        } elseif ($statuses->contains('closed') || $statuses->contains('partial')) {
            $this->status = 'partial';
        } else {
            $this->status = 'open';
        }

        $this->save();
    }
}