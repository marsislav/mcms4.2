@extends('admin.layout')
@section('title', __('Менюта'))

@section('content')
<div class="table-wrap">
<table class="table">
    <thead><tr><th>{{ __('Меню') }}</th><th>{{ __('Позиция') }}</th><th>{{ __('Елементи') }}</th><th></th></tr></thead>
    <tbody>
    @foreach ($menus as $menu)
        <tr>
            <td><strong>{{ $menu->name }}</strong></td>
            <td><code>{{ $menu->location }}</code></td>
            <td>{{ $menu->items_count }}</td>
            <td class="row-actions">{!! __('<a class="btn sm" href=":p1">Редакция</a>', ['p1' => e(route('admin.menus.edit', $menu))]) !!}</td>
        </tr>
    @endforeach
    </tbody>
</table>
</div>
@endsection
