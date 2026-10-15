<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Language extends Model
{
    protected $fillable = ['code', 'name', 'native_name', 'is_default', 'is_active', 'sort'];

    protected $casts = ['is_default' => 'boolean', 'is_active' => 'boolean'];

    public static function flush(): void
    {
        Cache::forget('mcms.languages');
    }
}
