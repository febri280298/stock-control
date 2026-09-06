<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Part extends Model
{
    protected $fillable = [
    'model', 'commodity', 'part_name', 'part_number', 'supplier', 'stock', 'min_stock',
    'price', 'price_valid_from', 'price_valid_until', 'tarikan_sales',
    ];

    protected $casts = [
        'price'              => 'decimal:2',
        'tarikan_sales'      => 'decimal:2',
        'price_valid_from'   => 'date:Y-m-d',
        'price_valid_until'  => 'date:Y-m-d',
    ];

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}