@extends('admin.layout')
@section('title', 'Redirects (301/302)')

@section('content')
<section class="card">
    <h2>New redirect</h2>
    <form method="post" action="{{ route('admin.redirects.store') }}" class="grid-form">
        @csrf
        <label>From path <input name="from_path" required maxlength="190" placeholder="/old-page" value="{{ old('from_path') }}"></label>
        <label>To <input name="to_url" required maxlength="500" placeholder="/new-page or https://…" value="{{ old('to_url') }}"></label>
        <label>Code
            <select name="code"><option value="301">301 — permanent</option><option value="302">302 — temporary</option></select>
        </label>
        <div><button class="btn primary">Save</button></div>
    </form>
    <p class="muted">Redirects apply only when the address does not exist (404).</p>
</section>

<div class="table-wrap">
<table class="table">
    <thead><tr><th>From</th><th>To</th><th>Code</th><th>Hits</th><th></th></tr></thead>
    <tbody>
    @forelse ($redirects as $r)
        <tr>
            <td><code>{{ $r->from_path }}</code></td>
            <td>{{ $r->to_url }}</td>
            <td>{{ $r->code }}</td>
            <td>{{ $r->hits }}</td>
            <td class="row-actions"><form method="post" action="{{ route('admin.redirects.destroy', $r) }}" data-confirm="Delete this redirect?">@csrf @method('DELETE')<button class="btn sm danger">Delete</button></form></td>
        </tr>
    @empty
        <tr><td colspan="5" class="muted center">No redirects.</td></tr>
    @endforelse
    </tbody>
</table>
</div>
{{ $redirects->links() }}
@endsection
