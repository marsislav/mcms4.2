<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Blocks;
use App\Support\Sanitizer;
use App\Support\Tables;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Reusable blocks ([block slug]) and content templates (starting content for new posts/pages). */
class BlockController extends Controller
{
    public function index()
    {
        $ready = Tables::has('blocks');

        return view('admin.blocks.index', ['ready' => $ready, 'rows' => $ready ? DB::table('blocks')->orderBy('kind')->orderBy('name')->get() : collect()]);
    }

    public function create(Request $request)
    {
        abort_unless(Tables::has('blocks'), 404);

        return view('admin.blocks.form', ['row' => null, 'kind' => $request->query('kind') === 'template' ? 'template' : 'block']);
    }

    public function edit(int $id)
    {
        abort_unless(Tables::has('blocks'), 404);
        $row = DB::table('blocks')->where('id', $id)->first();
        abort_unless($row, 404);

        return view('admin.blocks.form', ['row' => $row, 'kind' => $row->kind]);
    }

    public function store(Request $request)
    {
        return $this->save($request, null);
    }

    public function update(Request $request, int $id)
    {
        return $this->save($request, $id);
    }

    private function save(Request $request, ?int $id)
    {
        abort_unless(Tables::has('blocks'), 404);
        $data = $request->validate([
            'kind' => 'required|in:block,template',
            'name' => 'required|string|max:120',
            'slug' => 'nullable|string|max:80',
            'applies_to' => 'nullable|in:any,post,page',
            'body' => 'nullable|string|max:100000',
            'i18n' => 'nullable|array',
        ]);
        $locales = mcms_locales();
        $def = mcms_default_locale();
        $i18n = [];
        foreach ((array) ($data['i18n'] ?? []) as $loc => $html) {
            if (in_array($loc, $locales, true) && $loc !== $def && trim((string) $html) !== '') {
                $i18n[$loc] = Sanitizer::clean((string) $html);
            }
        }
        $taken = DB::table('blocks')->when($id, fn ($q) => $q->where('id', '!=', $id))->pluck('slug')->all();
        $slug = trim((string) ($data['slug'] ?? '')) !== '' ? Blocks::slugify($data['slug'], $taken) : Blocks::slugify($data['name'], $taken);
        $row = [
            'kind' => $data['kind'], 'name' => trim(strip_tags($data['name'])), 'slug' => $slug,
            'applies_to' => $data['kind'] === 'template' ? ($data['applies_to'] ?? 'any') : 'any',
            'body' => Sanitizer::clean((string) ($data['body'] ?? '')),
            'i18n' => $i18n ? json_encode($i18n, JSON_UNESCAPED_UNICODE) : null,
            'updated_at' => now(),
        ];
        if ($id) {
            DB::table('blocks')->where('id', $id)->update($row);
        } else {
            $id = DB::table('blocks')->insertGetId($row + ['created_at' => now()]);
        }
        audit('block.save', $slug);

        return redirect()->route('admin.blocks.edit', $id)->with('status', __('Записано.'));
    }

    public function destroy(int $id)
    {
        abort_unless(Tables::has('blocks'), 404);
        DB::table('blocks')->where('id', $id)->delete();
        audit('block.delete', (string) $id);

        return redirect()->route('admin.blocks.index')->with('status', __('Изтрито.'));
    }
}
