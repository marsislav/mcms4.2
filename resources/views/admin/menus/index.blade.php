@extends('admin.layout')
@section('title', 'Menus')

@section('content')
<div class="table-wrap">
<table class="table">
    <thead><tr><th>Menu</th><th>Position</th><th>Items</th><th></th></tr></thead>
    <tbody>
    @foreach ($menus as $menu)
        <tr>
            <td><strong>{{ $menu->name }}</strong></td>
            <td><code>{{ $menu->location }}</code></td>
            <td>{{ $menu->items_count }}</td>
            <td class="row-actions"><a class="btn sm" href="{{ route('admin.menus.edit', $menu) }}">Edit</a></td>
        </tr>
    @endforeach
    </tbody>
</table>
</div>
@endsection
