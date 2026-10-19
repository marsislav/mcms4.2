@php $me = auth()->user(); @endphp
<ul class="widget-list">
    @if($me && $me->active)
        @if($me->allows('content'))
            <li><a href="{{ route('admin.dashboard') }}">{{ t('account.panel', __('Табло')) }}</a></li>
            <li><a href="{{ route('admin.posts.create') }}">{{ t('account.new', __('Нова статия')) }}</a></li>
        @endif
        <li><a href="{{ route('admin.profile') }}">{{ t('account.profile', __('Моят профил')) }}</a></li>
        @if($au = \App\Support\Members::authorUrl($me))<li><a href="{{ $au }}">{{ t('account.public', __('Моята страница')) }}</a></li>@endif
    @else
        <li><a href="{{ route('admin.login') }}">{{ t('account.login', __('Вход')) }}</a></li>
        @if(\App\Support\Members::registrationOpen())<li><a href="{{ route('register') }}">{{ t('account.register', __('Регистрация')) }}</a></li>@endif
    @endif
</ul>
