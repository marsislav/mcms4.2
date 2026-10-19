@extends('admin.layout')
@php($exists = $user->exists)
@section('title', $exists ? __('Редакция на потребител') : __('Нов потребител'))

@section('actions'){!! __('<a class="btn" href=":p1">← Потребители</a>', ['p1' => e(route('admin.users.index'))]) !!}@endsection

@section('content')
<section class="card narrow-card">
<form method="post" action="{{ $exists ? route('admin.users.update', $user) : route('admin.users.store') }}" class="stack">
    @csrf
    @if($exists) @method('PUT') @endif

    <label>{{ __('Име') }} <input name="name" value="{{ old('name', $user->name) }}" required maxlength="100"></label>
    <label>{{ __('Имейл') }} <input type="email" name="email" value="{{ old('email', $user->email) }}" required maxlength="190"></label>
    <label>{{ __('Роля') }}
        <select name="role" @disabled($exists && $user->id === auth()->id())>
            @foreach (config('mcms.role_labels') as $key => $label)
                <option value="{{ $key }}" @selected(old('role', $user->role) === $key)>{{ __($label) }}</option>
            @endforeach
        </select>
    </label>
    <label>{{ $exists ? __('Нова парола (празно = без промяна)') : __('Парола') }}
        <input type="password" name="password" @if(! $exists) required @endif minlength="12" autocomplete="new-password">
    </label>
    {!! __('<small class="muted">Минимум 12 знака, малки и главни букви, цифра.</small>') !!}

    @if($exists)
        @if($user->id !== auth()->id())
        <label class="check"><input type="checkbox" name="active" value="1" @checked(old('active', $user->active))> {{ __('Активен профил') }}</label>
        @endif
        <label class="check"><input type="checkbox" name="unlock" value="1"> {{ __('Отключи (нулира неуспешните опити)') }}</label>
    @endif

    <button class="btn primary">{{ __('Запази') }}</button>
</form>
</section>

<section class="card narrow-card">
    <h3>{{ __('Права на ролите') }}</h3>
    <ul>
        <li>{!! __('<strong>Администратор</strong> — всичко.') !!}</li>
        <li>{!! __('<strong>Редактор</strong> — цялото съдържание, категории, медия, менюта, съобщения, пренасочвания и преводи.') !!}</li>
        <li>{!! __('<strong>Автор</strong> — само собствени страници/публикации и медия.') !!}</li>
    </ul>
</section>
@endsection
