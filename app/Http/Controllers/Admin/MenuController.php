<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Content;
use App\Models\Menu;
use App\Models\MenuItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MenuController extends Controller
{
    public function index()
    {
        return view('admin.menus.index', ['menus' => Menu::withCount('items')->orderBy('id')->get()]);
    }

    public function edit(Menu $menu)
    {
        return view('admin.menus.edit', [
            'menu' => $menu,
            'items' => $menu->items()->get(),
            'contents' => Content::with('translations')->orderBy('type')->orderBy('id')->get(),
            'languages' => mcms_languages(),
            'builtin' => MenuItem::BUILTIN,
        ]);
    }

    public function update(Request $request, Menu $menu)
    {
        $request->validate([
            'items' => 'nullable|array|max:100',
            'items.*.type' => 'required|in:content,builtin,custom',
            'items.*.sort' => 'nullable|integer|min:0|max:65000',
            'items.*.parent_id' => 'nullable|integer',
            'items.*.content_id' => 'nullable|integer|exists:contents,id',
            'items.*.builtin' => 'nullable|in:'.implode(',', MenuItem::BUILTIN),
            'items.*.url' => 'nullable|string|max:500',
            'items.*.labels' => 'nullable|array',
            'items.*.labels.*' => 'nullable|string|max:120',
        ]);

        $valid = array_flip(mcms_locales());

        DB::transaction(function () use ($request, $menu, $valid) {
            $existingIds = $menu->items()->pluck('id')->all();
            $keepIds = [];
            $rows = $request->input('items', []);

            // Pass 1: save every row without parent
            $map = [];
            foreach ($rows as $key => $row) {
                if (! empty($row['delete'])) {
                    continue;
                }

                $type = $row['type'];
                $url = null;
                $contentId = null;

                if ($type === 'content') {
                    $contentId = (int) ($row['content_id'] ?? 0) ?: null;
                    if (! $contentId) {
                        continue;
                    }
                } elseif ($type === 'builtin') {
                    $url = $row['builtin'] ?? null;
                    if (! $url) {
                        continue;
                    }
                } else {
                    $url = trim((string) ($row['url'] ?? ''));
                    if (! $this->safeUrl($url)) {
                        throw ValidationException::withMessages(["items.$key.url" => 'Невалиден адрес. Позволени: /път, #котва, http(s)://, mailto:, tel:']);
                    }
                }

                $labels = array_filter(
                    array_intersect_key(array_map(fn ($v) => trim(strip_tags((string) $v)), $row['labels'] ?? []), $valid),
                    fn ($v) => $v !== ''
                );

                $payload = [
                    'menu_id' => $menu->id, 'type' => $type, 'content_id' => $contentId, 'url' => $url,
                    'labels' => $labels ?: null, 'sort' => (int) ($row['sort'] ?? 0), 'new_tab' => ! empty($row['new_tab']), 'parent_id' => null,
                ];

                $id = (int) ($row['id'] ?? 0);
                if ($id && in_array($id, $existingIds, true)) {
                    MenuItem::where('id', $id)->update($payload + []);
                    $item = MenuItem::find($id);
                } else {
                    $item = MenuItem::create($payload);
                }
                $keepIds[] = $item->id;
                $map[$key] = $item->id;
            }

            // Pass 2: parents (max depth 2, parent must be a kept top-level row of this menu)
            foreach ($rows as $key => $row) {
                $parentKey = $row['parent_id'] ?? null;
                if (! isset($map[$key]) || ! $parentKey) {
                    continue;
                }
                $parentId = in_array((int) $parentKey, $keepIds, true) ? (int) $parentKey : null;
                if ($parentId && $parentId !== $map[$key]) {
                    $parentRow = MenuItem::find($parentId);
                    if ($parentRow && $parentRow->parent_id === null) {
                        MenuItem::where('id', $map[$key])->update(['parent_id' => $parentId]);
                    }
                }
            }

            // A parent may not itself have a parent
            MenuItem::where('menu_id', $menu->id)->whereNotNull('parent_id')
                ->whereIn('parent_id', MenuItem::where('menu_id', $menu->id)->whereNotNull('parent_id')->pluck('id'))
                ->update(['parent_id' => null]);

            MenuItem::where('menu_id', $menu->id)->whereNotIn('id', $keepIds ?: [0])->delete();
        });

        audit('menu.update', $menu->location);

        return back()->with('status', 'Менюто е записано.');
    }

    private function safeUrl(string $url): bool
    {
        if ($url === '') {
            return false;
        }
        if (preg_match('/[\x00-\x1f\x7f\s]/', $url)) {
            return false;
        }

        return (bool) preg_match('~^(/(?!/)|#|https?://|mailto:|tel:)~i', $url);
    }
}
