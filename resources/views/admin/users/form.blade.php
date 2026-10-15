@extends('admin.layout')
@php($exists = $user->exists)
@section('title', $exists ? 'Edit user' : 'New user')

@section('actions')<a class="btn" href="{{ route('admin.users.index') }}">← Users</a>@endsection

@section('content')
<section class="card narrow-card">
<form method="post" action="{{ $exists ? route('admin.users.update', $user) : route('admin.users.store') }}" class="stack">
    @csrf
    @if($exists) @method('PUT') @endif

    <label>Name <input name="name" value="{{ old('name', $user->name) }}" required maxlength="100"></label>
    <label>Email <input type="email" name="email" value="{{ old('email', $user->email) }}" required maxlength="190"></label>
    <label>Role
        <select name="role" @disabled($exists && $user->id === auth()->id())>
            @foreach (config('mcms.role_labels') as $key => $label)
                <option value="{{ $key }}" @selected(old('role', $user->role) === $key)>{{ $label }}</option>
            @endforeach
        </select>
    </label>
    <label>{{ $exists ? 'New password (leave blank to keep current)' : 'Password' }}
        <input type="password" name="password" @if(! $exists) required @endif minlength="12" autocomplete="new-password">
    </label>
    <small class="muted">At least 12 characters, with lowercase and uppercase letters and a digit.</small>

    @if($exists)
        @if($user->id !== auth()->id())
        <label class="check"><input type="checkbox" name="active" value="1" @checked(old('active', $user->active))> Active account</label>
        @endif
        <label class="check"><input type="checkbox" name="unlock" value="1"> Unlock (resets failed attempts)</label>
        @if($user->totp_enabled)
        <label class="check"><input type="checkbox" name="reset_2fa" value="1"> Disable 2FA (lost device)</label>
        @endif
    @endif

    <button class="btn primary">Save</button>
</form>
</section>

<section class="card narrow-card">
    <h3>Role permissions</h3>
    <ul>
        <li><strong>Administrator</strong> — everything.</li>
        <li><strong>Editor</strong> — all content, categories, media, menus, messages, redirects and translations.</li>
        <li><strong>Author</strong> — own pages/posts and media only.</li>
    </ul>
</section>
@endsection
