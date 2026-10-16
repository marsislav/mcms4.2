@extends('admin.layout')
@php($exists = $user->exists)
@section('title', $exists ? 'Редакция на потребител' : 'Нов потребител')

@section('actions')<a class="btn" href="{{ route('admin.users.index') }}">← Потребители</a>@endsection

@section('content')
<section class="card narrow-card">
<form method="post" action="{{ $exists ? route('admin.users.update', $user) : route('admin.users.store') }}" class="stack">
    @csrf
    @if($exists) @method('PUT') @endif

    <label>Име <input name="name" value="{{ old('name', $user->name) }}" required maxlength="100"></label>
    <label>Имейл <input type="email" name="email" value="{{ old('email', $user->email) }}" required maxlength="190"></label>
    <label>Роля
        <select name="role" @disabled($exists && $user->id === auth()->id())>
            @foreach (config('mcms.role_labels') as $key => $label)
                <option value="{{ $key }}" @selected(old('role', $user->role) === $key)>{{ $label }}</option>
            @endforeach
        </select>
    </label>
    <label>{{ $exists ? 'Нова парола (празно = без промяна)' : 'Парола' }}
        <input type="password" name="password" @if(! $exists) required @endif minlength="12" autocomplete="new-password">
    </label>
    <small class="muted">Минимум 12 знака, малки и главни букви, цифра.</small>

    @if($exists)
        @if($user->id !== auth()->id())
        <label class="check"><input type="checkbox" name="active" value="1" @checked(old('active', $user->active))> Активен профил</label>
        @endif
        <label class="check"><input type="checkbox" name="unlock" value="1"> Отключи (нулира неуспешните опити)</label>
    @endif

    <button class="btn primary">Запази</button>
</form>
</section>

<section class="card narrow-card">
    <h3>Права на ролите</h3>
    <ul>
        <li><strong>Администратор</strong> — всичко.</li>
        <li><strong>Редактор</strong> — цялото съдържание, категории, медия, менюта, съобщения, пренасочвания и преводи.</li>
        <li><strong>Автор</strong> — само собствени страници/публикации и медия.</li>
    </ul>
</section>
@endsection
