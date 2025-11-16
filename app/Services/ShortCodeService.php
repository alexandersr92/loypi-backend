<?php

namespace App\Services;

use Illuminate\Support\Str;

class ShortCodeService
{
    public static function generate(): string
    {
        do {
            $code = strtoupper(Str::random(6));
        } while (\App\Models\Customer::where('short_code', $code)->exists());
        
        return $code;
    }
}

