<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = [
        'part_id', 'type', 'qty', 'qty_ok', 'date', 'time',
        'status_qc', 'keterangan', 'keterangan_reject', 'supplier', 'tujuan', 'user_id'
    ];

    public function part()
    {
        return $this->belongsTo(Part::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
