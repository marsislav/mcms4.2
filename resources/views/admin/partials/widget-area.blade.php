{{-- $col, $list (array of widgets), $label --}}
<div data-widget-area>
    <div data-widget-list data-col="{{ $col }}">
        @foreach ($list as $i => $w)
            @include('admin.partials.widget-card', ['col' => $col, 'idx' => 'e'.$i, 'w' => $w])
        @endforeach
    </div>
    <div class="btn-row widget-add">
        <select data-widget-type aria-label="Тип уиджет">
            @foreach (\App\Support\Widgets::TYPES as $type => $tl)<option value="{{ $type }}">{{ $tl }}</option>@endforeach
        </select>
        <button type="button" class="btn sm primary" data-widget-add>＋ Добави уиджет</button>
    </div>
</div>
