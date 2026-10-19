@php $items = \App\Models\Menu::tree($w['menu'] ?? 'footer'); @endphp
@if($items)
<nav aria-label="{{ \App\Support\Widgets::text($w, 'title') ?: 'Menu' }}">
    <ul class="widget-list">
        @foreach ($items as $item)
            <li @class([($item['css_class'] ?? '') => ! empty($item['css_class'])]) @if(! empty($item['css_id'])) id="{{ $item['css_id'] }}" @endif><a href="{{ $item['url'] }}" @if($item['new_tab']) target="_blank" rel="noopener noreferrer" @endif>{{ $item['label'] }}</a></li>
        @endforeach
    </ul>
</nav>
@endif
