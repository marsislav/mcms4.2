@extends('admin.layout')
@section('title', 'Меню: '.$menu->name)

@section('actions')<a class="btn" href="{{ route('admin.menus.index') }}">← Менюта</a>@endsection

@section('content')
@php
    $contentLabel = fn ($c) => ($c->type === 'post' ? '[публикация] ' : '[страница] ').$c->adminTitle();
    $rows = $items->map(fn ($i) => $i)->all();
    // two blank rows for adding new items
    $rows[] = new \App\Models\MenuItem(['type' => 'content']);
    $rows[] = new \App\Models\MenuItem(['type' => 'builtin']);
    $parents = $items->whereNull('parent_id');
@endphp

<form method="post" action="{{ route('admin.menus.update', $menu) }}">
    @csrf @method('PUT')
    <p class="muted">Максимум 2 нива. Елементи към несъществуващо/непубликувано съдържание не се показват. Оставете етикета празен за автоматичен.</p>

    <div class="table-wrap">
    <table class="table menu-table">
        <thead><tr><th>Тип</th><th>Цел</th><th>Етикети</th><th>Родител</th><th>№</th><th>Нов прозорец</th><th>Изтрий</th></tr></thead>
        <tbody>
        @foreach ($rows as $k => $item)
            <tr>
                <td>
                    <input type="hidden" name="items[{{ $k }}][id]" value="{{ $item->id }}">
                    <select name="items[{{ $k }}][type]" data-menu-type>
                        <option value="content" @selected($item->type === 'content')>Съдържание</option>
                        <option value="builtin" @selected($item->type === 'builtin')>Вграден</option>
                        <option value="custom" @selected($item->type === 'custom')>Линк</option>
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
                    <input name="items[{{ $k }}][url]" data-for="custom" placeholder="/път или https://…" maxlength="500" value="{{ $item->type === 'custom' ? $item->url : '' }}">
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
                            @if($p->id !== $item->id)<option value="{{ $p->id }}" @selected($item->parent_id === $p->id)>#{{ $p->id }} {{ $p->label(mcms_default_locale()) ?? ($p->url ?? 'елемент') }}</option>@endif
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
    <button class="btn primary">Запази менюто</button>
    <p class="muted">Нужни още редове? Запазете и отворете страницата отново — добавят се два нови празни реда.</p>
</form>
@endsection
