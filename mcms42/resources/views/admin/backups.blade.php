@extends('admin.layout')
@section('title', 'Архиви')

@section('content')
<section class="card">
    <h2>Нов архив</h2>
    <div class="btn-row">
        <form method="post" action="{{ route('admin.backups.create') }}">@csrf<input type="hidden" name="kind" value="db"><button class="btn primary">Архив на базата данни (.sql.gz)</button></form>
        @if($zip)
        <form method="post" action="{{ route('admin.backups.create') }}">@csrf<input type="hidden" name="kind" value="uploads"><button class="btn">Архив на качените файлове (.zip)</button></form>
        @endif
    </div>
    <p class="muted">Файловете се пазят извън публичната директория (storage/app/backups). Пазят се по 10 последни от вид. Възстановяване: импортирайте .sql.gz през phpMyAdmin. Архивите съдържат чувствителни данни — изтегляйте ги по сигурен канал и ги триете след това.</p>
</section>

<div class="table-wrap">
<table class="table">
    <thead><tr><th>Файл</th><th>Размер</th><th>Създаден</th><th></th></tr></thead>
    <tbody>
    @forelse ($backups as $b)
        <tr>
            <td><code>{{ $b['name'] }}</code></td>
            <td>{{ number_format($b['size'] / 1024, 0) }} KB</td>
            <td class="muted">{{ date('d.m.Y H:i', $b['time']) }}</td>
            <td class="row-actions">
                <a class="btn sm" href="{{ route('admin.backups.download', $b['name']) }}">Изтегли</a>
                <form method="post" action="{{ route('admin.backups.destroy', $b['name']) }}" data-confirm="Да изтрия ли архива?">@csrf @method('DELETE')<button class="btn sm danger">Изтрий</button></form>
            </td>
        </tr>
    @empty
        <tr><td colspan="4" class="muted center">Няма архиви.</td></tr>
    @endforelse
    </tbody>
</table>
</div>
@endsection
