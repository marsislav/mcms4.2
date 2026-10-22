{{-- Front-end admin bar for logged-in staff: quick "new" links + edit link for the current content. Option: Settings > Administration. --}}
@auth
@php
    $ab = auth()->user();
    $abPost = $ab->allows('content');
    $abPage = $ab->allows('pages');
    $abCat = $ab->allows('categories');
@endphp
@if(\App\Support\Options::on('adminbar') && ($abPost || $abPage))
<div class="adminbar" role="navigation" aria-label="{{ __('Администрация') }}">
    <a href="{{ route('admin.dashboard') }}">{{ __('Табло') }}</a>
    @if($abPost)<a href="{{ route('admin.posts.create') }}">＋ {{ __('Статия') }}</a>@endif
    @if($abPage)<a href="{{ route('admin.pages.create') }}">＋ {{ __('Страница') }}</a>@endif
    @if($abCat)<a href="{{ route('admin.categories.index') }}">＋ {{ __('Категория') }}</a>@endif
    @isset($content)
        @if($content->type === 'post' ? $abPost : $abPage)<a class="adminbar-edit" href="{{ route($content->type === 'post' ? 'admin.posts.edit' : 'admin.pages.edit', $content) }}">✎ {{ __('Редактирай') }}</a>@endif
    @endisset
</div>
@endif
@endauth
