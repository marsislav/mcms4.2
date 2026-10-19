@extends('admin.layout')
@section('title', __('Моят профил'))

@section('content')
<div class="cols">
    <section class="card">
        <h2>{{ __('Данни') }}</h2>
        <form method="post" action="{{ route('admin.profile.info') }}" class="stack">
            @csrf
            <label>{{ __('Име') }} <input name="name" value="{{ old('name', $user->name) }}" required maxlength="100"></label>
            <label>{{ __('Имейл') }} <input value="{{ $user->email }}" disabled></label>
            <label>{{ __('Текуща парола (за потвърждение)') }} <input type="password" name="current_password" required autocomplete="current-password"></label>
            <button class="btn primary">{{ __('Запази') }}</button>
        </form>
    </section>

    @if($members)
    <section class="card wide">
        <h2>{{ __('Резюме') }}</h2>
        <p class="muted">{{ __('Показва се на публичната ви страница') }}@if($url = \App\Support\Members::authorUrl($user)): <a href="{{ $url }}" target="_blank" rel="noopener">{{ $url }}</a>@elseif($user->username) {{ __('(страница на автор имат само сътрудници, автори и редактори)') }}@endif {{ __('— и под вашите статии.') }}</p>
        <form method="post" action="{{ route('admin.profile.bio') }}" class="stack">
            @csrf
            <label>{{ __('За мен / резюме') }} <textarea name="bio" rows="14" data-editor maxlength="30000">{{ old('bio', $user->bio) }}</textarea></label>
            <label>{{ __('Уебсайт') }} <input type="url" name="website" value="{{ old('website', $user->website) }}" maxlength="190" placeholder="https://"></label>
            <button class="btn primary">{{ __('Запази резюмето') }}</button>
        </form>
    </section>
    @else
    <section class="card"><p class="muted">{{ __('Профилът с резюме се включва след „Обнови базата“ (Табло).') }}</p></section>
    @endif

    <section class="card">
        <h2>{{ __('Смяна на парола') }}</h2>
        <form method="post" action="{{ route('admin.profile.password') }}" class="stack">
            @csrf
            <label>{{ __('Текуща парола') }} <input type="password" name="current_password" required autocomplete="current-password"></label>
            <label>{{ __('Нова парола') }} <input type="password" name="password" required minlength="12" autocomplete="new-password"></label>
            <label>{{ __('Повторете') }} <input type="password" name="password_confirmation" required minlength="12" autocomplete="new-password"></label>
            {!! __('<small class="muted">Минимум 12 знака, малки и главни букви, цифра. Другите ви сесии ще бъдат прекратени.</small>') !!}
            <button class="btn primary">{{ __('Смени паролата') }}</button>
        </form>
    </section>

    <section class="card">
        <h2>{{ __('Сесия') }}</h2>
        <p class="muted">{{ __('Последен вход: :p1 от', ['p1' => $user->last_login_at?->format('d.m.Y H:i') ?? '—']) }} {{ $user->last_login_ip ?? '—' }}</p>
        <p class="muted">{{ __('Автоматичен изход след :p1 мин. неактивност.', ['p1' => config('mcms.idle_minutes')]) }}</p>
    </section>
</div>
@if($members)
@push('styles')
<link rel="stylesheet" href="{{ asset_v('assets/css/grid.css') }}">
<link rel="stylesheet" href="{{ asset('assets/vendor/ckeditor5/ckeditor5.css') }}?v=49.0.0">
@endpush
@push('scripts')
<script src="{{ asset('assets/vendor/ckeditor5/ckeditor5.umd.js') }}?v=49.0.0" nonce="{{ csp_nonce() }}" defer></script>
@if(app()->getLocale() === 'bg')<script src="{{ asset('assets/vendor/ckeditor5/translations/bg.umd.js') }}?v=49.0.0" nonce="{{ csp_nonce() }}" defer></script>@endif
<script src="{{ asset_v('assets/js/editor-fa.js') }}" nonce="{{ csp_nonce() }}" defer></script>
<script src="{{ asset_v('assets/js/editor-grid.js') }}" nonce="{{ csp_nonce() }}" defer></script>
<script src="{{ asset_v('assets/js/editor.js') }}" nonce="{{ csp_nonce() }}" defer></script>
@endpush
@endif
@endsection
