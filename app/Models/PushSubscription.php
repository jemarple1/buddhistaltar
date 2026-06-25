<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PushSubscription extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'visitor_token',
        'shrine',
        'endpoint',
        'endpoint_hash',
        'public_key',
        'auth_token',
    ];
}
