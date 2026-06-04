<?php

namespace Trail\Support;

use Illuminate\Support\Str;

class TraceId
{
    public static function make(): string
    {
        return (string) Str::uuid();
    }
}
