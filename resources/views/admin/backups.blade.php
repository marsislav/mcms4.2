@extends('admin.layout')
@section('title', 'Backups')

@section('content')
<section class="card">
    <h2>New backup</h2>
    <div class="btn-row">
        <form method="post" action="{{ route('admin.backups.create') }}">@csrf<input type="hidden" name="kind" value="db"><button class="btn primary">Database backup (.sql.gz)</button></form>
        @if($zip)
        <form method="post" action="{{ route('admin.backups.create') }}">@csrf<input type="hidden" name="kind" value="uploads"><button class="btn">Uploaded files backup (.zip)</button></form>
        @endif
    </div>
    <p class="muted">Files are stored outside the public directory (storage/app/backups). The 10 most recent of each kind are kept. To restore, import the .sql.gz file via phpMyAdmin. Backups contain sensitive data — download them over a secure channel and delete them afterwards.</p>
</section>

<div class="table-wrap">
<table class="table">
    <thead><tr><th>File</th><th>Size</th><th>Created</th><th></th></tr></thead>
    <tbody>
    @forelse ($backups as $b)
        <tr>
            <td><code>{{ $b['name'] }}</code></td>
            <td>{{ number_format($b['size'] / 1024, 0) }} KB</td>
            <td class="muted">{{ date('d.m.Y H:i', $b['time']) }}</td>
            <td class="row-actions">
                <a class="btn sm" href="{{ route('admin.backups.download', $b['name']) }}">Download</a>
                <form method="post" action="{{ route('admin.backups.destroy', $b['name']) }}" data-confirm="Delete this backup?">@csrf @method('DELETE')<button class="btn sm danger">Delete</button></form>
            </td>
        </tr>
    @empty
        <tr><td colspan="4" class="muted center">No backups.</td></tr>
    @endforelse
    </tbody>
</table>
</div>
@endsection
