<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FlowerOffering extends Model
{
    protected $fillable = [
        'name',
        'flower_type',
        'vase_color',
    ];
}
