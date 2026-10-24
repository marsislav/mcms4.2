@php $blockHtml = ! empty($w['block']) ? \App\Support\Blocks::expand('[block '.$w['block'].']') : ''; @endphp
@if($blockHtml !== '')<div class="prose">{!! \App\Support\Fx::expand(\App\Support\Sanitizer::renderEmbeds($blockHtml)) !!}</div>@endif
