@extends('site.layout')

@section('content')
<article class="entry narrow">
    <header><h1>{{ t('register.title', __('Регистрация')) }}</h1></header>
    <p class="lead">{{ t('register.intro', __('Създайте профил, за да пишете и редактирате свои статии и да добавите резюме.')) }}</p>

    @if(session('reg_ok'))<div class="notice ok" role="status">{{ session('reg_ok') }}</div>@endif
    @if(session('reg_error'))<div class="notice err" role="alert">{{ session('reg_error') }}</div>@endif
    @if($errors->any())<div class="notice err" role="alert"><ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

    @unless(session('reg_ok'))
    <form class="contact-form" method="post" action="{{ route('register.store') }}">
        @csrf
        <input type="hidden" name="ts" value="{{ $ts }}">
        <div class="hp" aria-hidden="true"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
        <label>{{ t('register.name', __('Име')) }} <input name="name" value="{{ old('name') }}" required minlength="2" maxlength="100" autocomplete="name"></label>
        <label>{{ t('register.email', __('Имейл')) }} <input type="email" name="email" value="{{ old('email') }}" required maxlength="190" autocomplete="email"></label>
        <label>{{ t('register.password', __('Парола')) }} <input type="password" name="password" required minlength="10" autocomplete="new-password"></label>
        <label>{{ t('register.password2', __('Повторете паролата')) }} <input type="password" name="password_confirmation" required minlength="10" autocomplete="new-password"></label>
        <small class="muted">{{ t('register.hint', __('Минимум 10 знака, с букви и цифри.')) }}</small>
        <button class="btn">{{ t('register.submit', __('Регистрация')) }}</button>
    </form>
    @endunless
    <p class="muted"><a href="{{ route('admin.login') }}">{{ t('register.have', __('Вече имате профил? Вход')) }}</a></p>
</article>
@endsection
