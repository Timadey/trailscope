<?php

namespace Trail\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrailStep extends Model
{
    protected $guarded = [];

    protected $casts = [
        'context' => 'array',
        'recorded_at' => 'datetime',
    ];

    public function trace(): BelongsTo
    {
        return $this->belongsTo(TrailTrace::class, 'trail_trace_id');
    }
}
