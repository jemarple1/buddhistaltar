<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FlowerOffering extends Model
{
    protected $fillable = [
        'shrine',
        'is_permanent',
        'name',
        'visitor_token',
        'flower_type',
        'vase_color',
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
