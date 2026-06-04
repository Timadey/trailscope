<?php

namespace Trail\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrailTrace extends Model
{
    protected $guarded = [];

    protected $casts = [
        'request' => 'array',
        'response' => 'array',
        'exception' => 'array',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function steps(): HasMany
    {
        return $this->hasMany(TrailStep::class);
    }
}
