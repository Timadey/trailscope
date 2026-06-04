<?php

namespace Trail\Models;

use Illuminate\Database\Eloquent\Model;

class TrailSignedLink extends Model
{
    protected $guarded = [];

    protected $casts = [
        'expires_at' => 'datetime',
    ];
}
