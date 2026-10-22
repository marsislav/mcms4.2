{{-- Rendered right after <body>: reading progress bar, front-end admin bar. $kind comes from @section('kind'). --}}
@php $pageKind = trim($__env->yieldContent('kind')) ?: 'list'; @endphp
@if(\App\Support\Design::progressFor($pageKind))
    <div class="read-progress" data-read-progress aria-hidden="true"><span></span></div>
@endif
@include('site.partials.adminbar')
@if(session('newsletter_flash'))<div class="notice ok flash-bar" role="status">{{ session('newsletter_flash') }}</div>@endif
