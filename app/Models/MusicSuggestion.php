<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MusicSuggestion extends Model
{
    protected $fillable = [
        'youtube_url',
        'suggested_by_name',
    ];
}
