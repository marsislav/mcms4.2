<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Translation extends Model
{
    public $timestamps = false;

    protected $fillable = ['key', 'locale', 'value'];

    public static function flush(?string $locale = null): void
    {
        if ($locale) {
            Cache::forget('mcms.tr.'.$locale);

            return;
        }
        foreach (Language::pluck('code') as $code) {
            Cache::forget('mcms.tr.'.$code);
        }
    }
}
