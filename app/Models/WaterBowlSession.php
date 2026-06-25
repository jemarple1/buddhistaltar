<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WaterBowlSession extends Model
{
    protected $fillable = [
        'token',
        'filled_positions',
        'expires_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'filled_positions' => 'array',
            'expires_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function isActive(): bool
    {
        return $this->completed_at === null && $this->expires_at->isFuture();
    }
}
