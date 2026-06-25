<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MantraRepetition extends Model
{
    protected $fillable = [
        'count',
    ];

    protected function casts(): array
    {
        return [
            'count' => 'integer',
        ];
    }
}
