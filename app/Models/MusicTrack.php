<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MusicTrack extends Model
{
    protected $fillable = [
        'shrine',
        'youtube_id',
        'youtube_start_seconds',
        'title',
        'thumbnail_url',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'youtube_start_seconds' => 'integer',
        ];
    }

    public function offerings(): HasMany
    {
        return $this->hasMany(MusicOffering::class);
    }
}
