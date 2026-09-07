<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    const UPDATED_AT = null; // log ga pernah diedit, cukup created_at aja

    protected $fillable = [
        'user_id', 'user_name', 'role', 'action',
        'subject_type', 'subject_id', 'description', 'meta',
    ];

    protected $casts = [
        'meta'       => 'array',
        'created_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
