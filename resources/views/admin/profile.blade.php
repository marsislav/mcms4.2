@extends('admin.layout')
@section('title', 'Моят профил')

@section('content')
@if ($recovery)
<section class="card warn-card">
    <h2>Кодове за възстановяване</h2>
    <p>Запишете ги на сигурно място. Всеки код работи <strong>само веднъж</strong> и няма да бъдат показани отново.</p>
    <pre class="codes">{{ implode("\n", $recovery) }}</pre>
</section>
@endif

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
        <h2>Двуфакторна защита (2FA)</h2>
        @if ($user->totp_enabled)
            <p class="ok-text">✓ Активна. Входът изисква код от приложение (Google Authenticator, Aegis, 2FAS…).</p>
            <form method="post" action="{{ route('admin.profile.2fa.recovery') }}" class="stack" data-confirm="Старите кодове ще спрат да работят. Продължавате ли?">
                @csrf
                <label>Парола <input type="password" name="current_password" required autocomplete="current-password"></label>
                <button class="btn">Нови кодове за възстановяване</button>
            </form>
            <hr>
            <form method="post" action="{{ route('admin.profile.2fa.disable') }}" class="stack" data-confirm="Изключване на 2FA намалява сигурността. Сигурни ли сте?">
                @csrf
                <label>Парола <input type="password" name="current_password" required autocomplete="current-password"></label>
                <label>Текущ 6-цифрен код <input name="code" required inputmode="numeric" maxlength="10" autocomplete="one-time-code"></label>
                <button class="btn danger">Изключи 2FA</button>
            </form>
        @elseif ($secret)
            <p>1. В приложението изберете „Добави акаунт → въвеждане на ключ“ и въведете:</p>
            <pre class="codes">{{ trim(chunk_split($secret, 4, ' ')) }}</pre>
            <p class="muted">Тип: базиран на време (TOTP), 6 цифри, 30 сек. Мобилно устройство: <a href="{{ $otpauth }}">отвори в приложението</a>.</p>
            <p>2. Потвърдете с текущия код:</p>
            <form method="post" action="{{ route('admin.profile.2fa.confirm') }}" class="stack">
                @csrf
                <label>6-цифрен код <input name="code" required inputmode="numeric" maxlength="10" autocomplete="one-time-code"></label>
                <label>Парола <input type="password" name="current_password" required autocomplete="current-password"></label>
                <button class="btn primary">Активирай 2FA</button>
            </form>
        @else
            <p>Добавя втори фактор при вход — дори изтекла парола няма да е достатъчна за влизане.</p>
            <form method="post" action="{{ route('admin.profile.2fa.start') }}">@csrf<button class="btn primary">Настрой 2FA</button></form>
        @endif
    </section>

    <section class="card">
        <h2>Сесия</h2>
        <p class="muted">Последен вход: {{ $user->last_login_at?->format('d.m.Y H:i') ?? '—' }} от {{ $user->last_login_ip ?? '—' }}</p>
        <p class="muted">Автоматичен изход след {{ config('mcms.idle_minutes') }} мин. неактивност.</p>
    </section>
</div>
@endsection
