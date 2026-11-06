@extends('admin.layout')
@section('title', __('Редакция на обява'))

@section('actions'){!! __('<a class="btn" href=":p1">← Обяви</a>', ['p1' => e(route('admin.listings.index'))]) !!}@endsection

@section('content')
<section class="card narrow-card">
@if($errors->any())<div class="flash err" role="alert"><ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
<form method="post" action="{{ route('admin.listings.update', $l->id) }}" class="stack">
    @csrf @method('PUT')
    @if($cats->isNotEmpty())
    <label>{{ __('Категория') }}
        <select name="category_id"><option value="">—</option>@foreach ($cats as $c)<option value="{{ $c->id }}" @selected((int) old('category_id', $l->category_id) === $c->id)>{{ $c->name }}</option>@endforeach</select>
    </label>
    @endif
    <label>{{ __('Заглавие') }} <input name="title" value="{{ old('title', $l->title) }}" required maxlength="160"></label>
    <label>{{ __('Описание') }} <textarea name="body" rows="8" required maxlength="5000">{{ old('body', $l->body) }}</textarea></label>
    <label>{{ __('Цена (празно = по договаряне)') }} <input type="number" name="price" step="0.01" min="0" value="{{ old('price', $l->price) }}"></label>
    <label>{{ __('Валута') }}
        <select name="currency">@foreach (['EUR', 'BGN'] as $c)<option value="{{ $c }}" @selected(old('currency', $l->currency) === $c)>{{ $c }}</option>@endforeach</select>
    </label>
    <label>{{ __('Населено място') }} <input name="city" value="{{ old('city', $l->city) }}" maxlength="80"></label>
    <label>{{ __('Контакт — име') }} <input name="contact_name" value="{{ old('contact_name', $l->contact_name) }}" required maxlength="80"></label>
    <label>{{ __('Контакт — телефон') }} <input name="contact_phone" value="{{ old('contact_phone', $l->contact_phone) }}" maxlength="40"></label>
    <label>{{ __('Контакт — имейл') }} <input type="email" name="contact_email" value="{{ old('contact_email', $l->contact_email) }}" maxlength="120"></label>
    <label>{{ __('Валидност (дни, при одобрение)') }} <input type="number" name="days" min="1" max="365" value="{{ old('days', $l->days) }}" required></label>
    <label>{{ __('Валидна до') }} <input type="date" name="expires_at" value="{{ old('expires_at', $l->expires_at?->format('Y-m-d')) }}"></label>
    <label>{{ __('Статус') }}
        <select name="status">
            @foreach (['pending' => __('Чака одобрение'), 'published' => __('Публикувана'), 'rejected' => __('Отхвърлена')] as $k => $v)
                <option value="{{ $k }}" @selected(old('status', $l->status) === $k)>{{ $v }}</option>
            @endforeach
        </select>
    </label>

    @if($images->isNotEmpty())
    <div>
        <strong>{{ __('Снимки') }}</strong>
        <div class="ls-admin-photos">
            @foreach ($images as $m)
                <label class="check"><img src="{{ $m->thumbUrl() }}" alt="" width="110"> <input type="checkbox" name="remove[]" value="{{ $m->id }}"> {{ __('Премахни') }}</label>
            @endforeach
        </div>
    </div>
    @endif

    <p class="muted">{{ __('Автор:') }} {{ $l->user?->name ?? '—' }} · {{ __('Прегледи:') }} {{ (int) $l->views }} · <a href="{{ $l->url() }}" target="_blank" rel="noopener">{{ __('Виж на сайта ↗') }}</a></p>
    <button class="btn primary">{{ __('Запази') }}</button>
</form>
</section>
@endsection
