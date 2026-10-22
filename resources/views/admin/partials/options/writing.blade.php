<div class="grid-form">
    <label>{{ __('Категория по подразбиране на нова публикация') }}
        <select name="default_category">
            <option value="">{{ __('— без —') }}</option>
            @foreach ($optCategories as $c)<option value="{{ $c->id }}" @selected($opt['default_category'] === (string) $c->id)>{{ $c->adminName() }}</option>@endforeach
        </select>
    </label>
    <label>{{ __('Статус на нов запис') }}
        <select name="default_status">
            <option value="draft" @selected($opt['default_status'] === 'draft')>{{ __('Чернова') }}</option>
            <option value="published" @selected($opt['default_status'] === 'published')>{{ __('Публикуван') }}</option>
        </select>
    </label>
</div>
<p class="muted">{{ __('Коментарите (отваряне, одобрение, нишки) са в таб „Дискусии“.') }}</p>

<div class="grid-form">
    <label>{!! __('Пази ревизии на статия <small class="muted">0 = не пазя</small>') !!} <input type="number" name="revisions_keep" min="0" max="500" value="{{ $opt['revisions_keep'] }}"></label>
</div>
