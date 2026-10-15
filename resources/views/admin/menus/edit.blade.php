@extends('admin.layout')
@section('title', 'Menu: '.$menu->name)

@section('actions')<a class="btn" href="{{ route('admin.menus.index') }}">← Menus</a>@endsection

@section('content')
@php
    $contentLabel = fn ($c) => ($c->type === 'post' ? '[post] ' : '[page] ').$c->adminTitle();
    $rows = $items->map(fn ($i) => $i)->all();
    // two blank rows for adding new items
    $rows[] = new \App\Models\MenuItem(['type' => 'content']);
    $rows[] = new \App\Models\MenuItem(['type' => 'builtin']);
    $parents = $items->whereNull('parent_id');
@endphp

<form method="post" action="{{ route('admin.menus.update', $menu) }}">
    @csrf @method('PUT')
    <p class="muted">Maximum 2 levels. Items pointing to missing or unpublished content are not shown. Leave the label empty to generate it automatically.</p>

    <div class="table-wrap">
    <table class="table menu-table">
        <thead><tr><th>Type</th><th>Target</th><th>Labels</th><th>Parent</th><th>#</th><th>New window</th><th>Delete</th></tr></thead>
        <tbody>
        @foreach ($rows as $k => $item)
            <tr>
                <td>
                    <input type="hidden" name="items[{{ $k }}][id]" value="{{ $item->id }}">
                    <select name="items[{{ $k }}][type]" data-menu-type>
                        <option value="content" @selected($item->type === 'content')>Content</option>
                        <option value="builtin" @selected($item->type === 'builtin')>Built-in</option>
                        <option value="custom" @selected($item->type === 'custom')>Link</option>
                    </select>
                </td>
                <td>
                    <select name="items[{{ $k }}][content_id]" data-for="content">
                        <option value="">—</option>
                        @foreach ($contents as $c)<option value="{{ $c->id }}" @selected($item->content_id === $c->id)>{{ $contentLabel($c) }}</option>@endforeach
                    </select>
                    <select name="items[{{ $k }}][builtin]" data-for="builtin">
                        <option value="">—</option>
                        @foreach ($builtin as $b)<option value="{{ $b }}" @selected($item->type === 'builtin' && $item->url === $b)>{{ $b }}</option>@endforeach
                    </select>
                    <input name="items[{{ $k }}][url]" data-for="custom" placeholder="/path or https://…" maxlength="500" value="{{ $item->type === 'custom' ? $item->url : '' }}">
                </td>
                <td>
                    @foreach ($languages as $lang)
                        <input name="items[{{ $k }}][labels][{{ $lang->code }}]" placeholder="{{ strtoupper($lang->code) }}" maxlength="120" value="{{ $item->labels[$lang->code] ?? '' }}">
                    @endforeach
                </td>
                <td>
                    <select name="items[{{ $k }}][parent_id]">
                        <option value="">—</option>
                        @foreach ($parents as $p)
                            @if($p->id !== $item->id)<option value="{{ $p->id }}" @selected($item->parent_id === $p->id)>#{{ $p->id }} {{ $p->label(mcms_default_locale()) ?? ($p->url ?? 'item') }}</option>@endif
                        @endforeach
                    </select>
                </td>
                <td><input type="number" name="items[{{ $k }}][sort]" value="{{ $item->sort ?? 0 }}" min="0" max="65000" class="narrow"></td>
                <td><input type="checkbox" name="items[{{ $k }}][new_tab]" value="1" @checked($item->new_tab)></td>
                <td>@if($item->exists)<input type="checkbox" name="items[{{ $k }}][delete]" value="1">@endif</td>
            </tr>
        @endforeach
        </tbody>
    </table>
    </div>
    <button class="btn primary">Save menu</button>
    <p class="muted">Need more rows? Save and reopen the page — two new empty rows are added.</p>
</form>
@endsection
