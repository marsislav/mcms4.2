@extends('admin.layout')
@section('title', 'Моят профил')

@section('content')
<div class="cols">
    <section class="card">
        <h2>Данни</h2>
        <form method="post" action="{{ route('admin.profile.info') }}" class="stack">
            @csrf
            <label>Име <input name="name" value="{{ old('name', $user->name) }}" required maxlength="100"></label>
            <label>Имейл <input value="{{ $user->email }}" disabled></label>
            <label>Текуща парола (за потвърждение) <input type="password" name="current_password" required autocomplete="current-password"></label>
            <button class="btn primary">Запази</button>
        </form>
    </section>

    <section class="card">
        <h2>Смяна на парола</h2>
        <form method="post" action="{{ route('admin.profile.password') }}" class="stack">
            @csrf
            <label>Текуща парола <input type="password" name="current_password" required autocomplete="current-password"></label>
            <label>Нова парола <input type="password" name="password" required minlength="12" autocomplete="new-password"></label>
            <label>Повторете <input type="password" name="password_confirmation" required minlength="12" autocomplete="new-password"></label>
            <small class="muted">Минимум 12 знака, малки и главни букви, цифра. Другите ви сесии ще бъдат прекратени.</small>
            <button class="btn primary">Смени паролата</button>
        </form>
    </section>

    <section class="card">
        <h2>Сесия</h2>
        <p class="muted">Последен вход: {{ $user->last_login_at?->format('d.m.Y H:i') ?? '—' }} от {{ $user->last_login_ip ?? '—' }}</p>
        <p class="muted">Автоматичен изход след {{ config('mcms.idle_minutes') }} мин. неактивност.</p>
    </section>
</div>
@endsection
