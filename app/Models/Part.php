<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Part extends Model
{
    protected $fillable = [
        'model', 'commodity', 'part_name', 'part_number', 'stock', 'min_stock'
    ];

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}
