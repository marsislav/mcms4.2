@php
    $icons = ['facebook' => ['fa-brands fa-facebook-f', 'Facebook'], 'instagram' => ['fa-brands fa-instagram', 'Instagram'], 'linkedin' => ['fa-brands fa-linkedin-in', 'LinkedIn'], 'youtube' => ['fa-brands fa-youtube', 'YouTube']];
    $any = collect($icons)->keys()->contains(fn ($k) => setting('social_'.$k));
@endphp
@if($any)
<ul class="widget-social">
    @foreach ($icons as $k => [$ic, $label])
        @if($u = setting('social_'.$k))<li><a href="{{ $u }}" rel="noopener noreferrer me" target="_blank" aria-label="{{ $label }}"><span class="{{ $ic }}" aria-hidden="true"></span> {{ $label }}</a></li>@endif
    @endforeach
</ul>
@endif
