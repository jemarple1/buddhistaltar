<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IncenseOffering extends Model
{
    protected $fillable = [
        'shrine',
        'name',
        'visitor_token',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }
}
