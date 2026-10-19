@extends('admin.layout')
@section('title', __('Меню: ').$menu->name)

@section('actions'){!! __('<a class="btn" href=":p1">← Менюта</a>', ['p1' => e(route('admin.menus.index'))]) !!}@endsection

@section('content')
@php
    $contentLabel = fn ($c) => ($c->type === 'post' ? __('[публикация] ') : __('[страница] ')).$c->adminTitle();
    $rows = $items->map(fn ($i) => $i)->all();
    // two blank rows for adding new items
    $rows[] = new \App\Models\MenuItem(['type' => 'content']);
    $rows[] = new \App\Models\MenuItem(['type' => 'builtin']);
    $parents = $items->whereNull('parent_id');
@endphp

<form method="post" action="{{ route('admin.menus.update', $menu) }}">
    @csrf @method('PUT')
    @unless($extras)
        <div class="flash err">{!! __('Полетата „CSS клас“ и „ID“ изискват обновяване на базата — натиснете „Обнови базата“ на Таблото (или изпълнете <code>database/upgrade/4.2-upgrade.sql</code>).') !!}</div>
    @endunless
    <p class="muted">{{ __('Максимум 2 нива. Елементи към несъществуващо/непубликувано съдържание не се показват. Оставете етикета празен за автоматичен.') }}</p>

    <div class="table-wrap">
    <table class="table menu-table">
        <thead><tr><th>{{ __('Тип') }}</th><th>{{ __('Цел') }}</th><th>{{ __('Етикети') }}</th>@if($extras)<th>{{ __('CSS клас / ID') }}</th>@endif<th>{{ __('Родител') }}</th><th>№</th><th>{{ __('Нов прозорец') }}</th><th>{{ __('Изтрий') }}</th></tr></thead>
        <tbody>
        @foreach ($rows as $k => $item)
            <tr>
                <td>
                    <input type="hidden" name="items[{{ $k }}][id]" value="{{ $item->id }}">
                    <select name="items[{{ $k }}][type]" data-menu-type>
                        <option value="content" @selected($item->type === 'content')>{{ __('Съдържание') }}</option>
                        <option value="builtin" @selected($item->type === 'builtin')>{{ __('Вграден') }}</option>
                        <option value="custom" @selected($item->type === 'custom')>{{ __('Линк') }}</option>
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
                    <input name="items[{{ $k }}][url]" data-for="custom" placeholder="{{ __('/път или https://…') }}" maxlength="500" value="{{ $item->type === 'custom' ? $item->url : '' }}">
                </td>
                <td>
                    @foreach ($languages as $lang)
                        <input name="items[{{ $k }}][labels][{{ $lang->code }}]" placeholder="{{ strtoupper($lang->code) }}" maxlength="120" value="{{ $item->labels[$lang->code] ?? '' }}">
                    @endforeach
                </td>
                @if($extras)
                <td>
                    <input name="items[{{ $k }}][css_class]" placeholder="{{ __('class (напр. btn highlight)') }}" maxlength="200" value="{{ old("items.$k.css_class", $item->css_class) }}" pattern="[A-Za-z_][A-Za-z0-9_\-]*(\s+[A-Za-z_][A-Za-z0-9_\-]*)*" title="{{ __('Букви, цифри, - и _, разделени с интервал') }}">
                    <input name="items[{{ $k }}][css_id]" placeholder="{{ __('id (напр. menu-shop)') }}" maxlength="80" value="{{ old("items.$k.css_id", $item->css_id) }}" pattern="[A-Za-z][A-Za-z0-9_\-:.]*" title="{{ __('Започва с буква; без интервали') }}">
                </td>
                @endif
                <td>
                    <select name="items[{{ $k }}][parent_id]">
                        <option value="">—</option>
                        @foreach ($parents as $p)
                            @if($p->id !== $item->id)<option value="{{ $p->id }}" @selected($item->parent_id === $p->id)>#{{ $p->id }} {{ $p->label(mcms_default_locale()) ?? ($p->url ?? __('елемент')) }}</option>@endif
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
    <button class="btn primary">{{ __('Запази менюто') }}</button>
    <p class="muted">{!! __('CSS клас и ID се добавят към <code>&lt;li&gt;</code> на елемента (както в WordPress), напр. <code>#menu-shop &gt; a {…}</code> или <code>.highlight &gt; a {…}</code>. ID трябва да е уникален.') !!}</p>
    <p class="muted">{{ __('Нужни още редове? Запазете и отворете страницата отново — добавят се два нови празни реда.') }}</p>
</form>
@endsection
