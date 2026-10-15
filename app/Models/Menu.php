<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    public $timestamps = false;

    protected $fillable = ['location', 'name'];

    public function items()
    {
        return $this->hasMany(MenuItem::class)->orderBy('sort')->orderBy('id');
    }

    /**
     * Render-ready tree for a location in the given locale.
     * Items pointing to missing/hidden content are skipped.
     *
     * @return array<int, array{label:string,url:string,new_tab:bool,children:array}>
     */
    public static function tree(string $location, ?string $locale = null): array
    {
        $locale ??= app()->getLocale();
        $menu = static::where('location', $location)->first();
        if (! $menu) {
            return [];
        }

        $items = $menu->items()->with('content.translations')->get();
        $byParent = [];
        foreach ($items as $item) {
            $resolved = $item->resolve($locale);
            if ($resolved) {
                $byParent[$item->parent_id ?? 0][] = ['id' => $item->id] + $resolved;
            }
        }

        $build = function (int $parent) use (&$build, $byParent) {
            $out = [];
            foreach ($byParent[$parent] ?? [] as $node) {
                $node['children'] = $parent === 0 ? $build($node['id']) : [];
                $out[] = $node;
            }

            return $out;
        };

        return apply_filters('site.menu', $build(0), $location, $locale);
    }
}
