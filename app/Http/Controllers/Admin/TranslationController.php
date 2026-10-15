<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Translation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TranslationController extends Controller
{
    public function index(Request $request)
    {
        $locales = mcms_locales();
        $q = trim((string) $request->query('q', ''));

        $rows = Translation::whereIn('locale', $locales)->orderBy('key')->get()->groupBy('key');
        $keys = $rows->keys()->when($q !== '', fn ($k) => $k->filter(fn ($key) => str_contains(mb_strtolower($key), mb_strtolower($q))
            || $rows[$key]->contains(fn ($r) => str_contains(mb_strtolower($r->value), mb_strtolower($q)))))->values();

        return view('admin.translations.index', [
            'keys' => $keys,
            'rows' => $rows,
            'languages' => mcms_languages(),
            'q' => $q,
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'tr' => 'nullable|array|max:1500',
            'tr.*' => 'array',
            'tr.*.*' => 'nullable|string|max:2000',
            'new_key' => ['nullable', 'regex:/^[a-z0-9_]+(\.[a-z0-9_]+)+$/', 'max:150'],
        ]);

        $locales = mcms_locales();
        $input = $request->input('tr', []);

        if ($new = $request->input('new_key')) {
            foreach ($locales as $loc) {
                $input[$new][$loc] = $request->input("new_value.$loc", '');
            }
        }

        DB::transaction(function () use ($input, $locales) {
            foreach ($input as $key => $values) {
                if (! is_string($key) || ! preg_match('/^[a-z0-9_]+(\.[a-z0-9_]+)+$/', $key)) {
                    continue;
                }
                foreach ($locales as $loc) {
                    $value = trim((string) ($values[$loc] ?? ''));
                    if ($value === '') {
                        Translation::where('key', $key)->where('locale', $loc)->delete();
                    } else {
                        Translation::updateOrCreate(['key' => $key, 'locale' => $loc], ['value' => $value]);
                    }
                }
            }
        });

        foreach ($locales as $loc) {
            Translation::flush($loc);
        }
        audit('translations.update');

        return back()->with('status', 'Преводите са записани.');
    }
}
