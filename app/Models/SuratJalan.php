<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SuratJalan extends Model
{
    protected $fillable = [
        'no_surat_jalan', 'delivery_to', 'date', 'project', 'no_po',
        'transaction_ids', 'file_path', 'user_id', 'user_name',
    ];

    protected $casts = [
        'transaction_ids' => 'array',
        'date'            => 'date:Y-m-d',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
