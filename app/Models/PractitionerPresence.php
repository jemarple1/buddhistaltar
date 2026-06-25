<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PractitionerPresence extends Model
{
    public $incrementing = false;

    protected $primaryKey = 'session_token';

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'session_token',
        'last_seen_at',
    ];

    protected function casts(): array
    {
        return [
            'last_seen_at' => 'datetime',
        ];
    }
}
