<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ButterLamp extends Model
{
    protected $fillable = [
        'shrine',
        'is_permanent',
        'name',
        'visitor_token',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'is_permanent' => 'boolean',
            'expires_at' => 'datetime',
        ];
    }
}
