{{-- Breadcrumb trail. Expects $crumbs (see Breadcrumbs::make) and @section('kind'). Options: Settings > SEO & navigation. --}}
@php
    $bcKind = trim($__env->yieldContent('kind')) ?: 'list';
    $bcShow = ! empty($crumbs) && \App\Support\Breadcrumbs::visible($bcKind) ? \App\Support\Breadcrumbs::forDisplay($crumbs) : [];
@endphp
@if(count($bcShow) > 1)
<nav class="breadcrumbs" aria-label="{{ t('site.breadcrumbs', __('Навигационна пътека')) }}" style="--bc-sep:'{{ \App\Support\Options::get('bc_separator') }}'">
    <ol>
        @foreach ($bcShow as $i => $c)
            @if(! empty($c['url']) && ! $loop->last)
                <li><a href="{{ $c['url'] }}">{{ $c['label'] }}</a></li>
            @else
                <li aria-current="page">{{ $c['label'] }}</li>
            @endif
        @endforeach
    </ol>
</nav>
@endif
