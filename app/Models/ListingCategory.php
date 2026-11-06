<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ListingCategory extends Model
{
    protected $fillable = ['name', 'sort'];

    public function listings()
    {
        return $this->hasMany(Listing::class, 'category_id');
    }

    /** Unique URL slug from the (Cyrillic) name. */
    public static function slugFor(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name, '-', 'bg') ?: 'kategoriya';
        $slug = $base;
        $i = 2;
        while (static::where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
