@extends('admin.layout')
@section('title', 'Менюта')

@section('content')
<div class="table-wrap">
<table class="table">
    <thead><tr><th>Меню</th><th>Позиция</th><th>Елементи</th><th></th></tr></thead>
    <tbody>
    @foreach ($menus as $menu)
        <tr>
            <td><strong>{{ $menu->name }}</strong></td>
            <td><code>{{ $menu->location }}</code></td>
            <td>{{ $menu->items_count }}</td>
            <td class="row-actions"><a class="btn sm" href="{{ route('admin.menus.edit', $menu) }}">Редакция</a></td>
        </tr>
    @endforeach
    </tbody>
</table>
</div>
@endsection
