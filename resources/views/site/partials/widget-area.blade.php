{{-- $widgets: list of widget arrays --}}
@foreach ($widgets as $w)
    @php $body = view()->exists('site.widgets.'.$w['type']) ? view('site.widgets.'.$w['type'], ['w' => $w])->render() : ''; @endphp
    @if(trim($body) !== '')
        @php $title = \App\Support\Widgets::text($w, 'title'); @endphp
        <section @class(['widget', 'widget-'.str_replace('_', '-', $w['type']), ($w['class'] ?? '') => ! empty($w['class'])]) id="widget-{{ $w['id'] }}">
            @if($title !== '' || ! empty($w['icon']))
                <h2 class="widget-title">@if(! empty($w['icon']))<span class="{{ $w['icon'] }}" aria-hidden="true"></span> @endif{{ $title }}</h2>
            @endif
            {!! $body !!}
        </section>
    @endif
@endforeach
