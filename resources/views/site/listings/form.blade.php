@extends('site.layout')

@section('content')
@include('site.partials.breadcrumbs')
@php $max = \App\Support\Listings::maxImages(); @endphp
<article class="entry narrow">
    <header><h1>{{ $edit ? t('listings.edit', 'Редакция на обява') : t('listings.add', 'Подай обява') }}</h1></header>

    @if(session('ls_error'))<div class="notice err" role="alert">{{ session('ls_error') }}</div>@endif
    @if($errors->any())<div class="notice err" role="alert"><ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
    @if(\App\Support\Listings::approval() && ! auth()->user()->allows('listings'))
        <p class="muted">{{ t('listings.moderated', 'Обявите се публикуват след преглед от администратор.') }}</p>
    @endif

    <form class="contact-form" method="post" enctype="multipart/form-data" action="{{ $edit ? lroute('listings.update', ['id' => $l->id]) : lroute('listings.store') }}">
        @csrf
        <div class="hp" aria-hidden="true"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>

        @if($cats->isNotEmpty())
        <label>{{ t('listings.f_category', 'Категория') }}
            <select name="category_id" required>
                <option value="">—</option>
                @foreach ($cats as $c)<option value="{{ $c->id }}" @selected((int) old('category_id', $l->category_id) === $c->id)>{{ $c->name }}</option>@endforeach
            </select>
        </label>
        @endif
        <label>{{ t('listings.f_title', 'Заглавие') }} <input name="title" value="{{ old('title', $l->title) }}" required minlength="5" maxlength="120"></label>
        <label>{{ t('listings.f_body', 'Описание') }} <textarea name="body" rows="8" required minlength="20" maxlength="5000">{{ old('body', $l->body) }}</textarea></label>

        <div class="ls-row">
            <label>{{ t('listings.f_price', 'Цена') }} <input type="number" name="price" step="0.01" min="0" max="99999999" value="{{ old('price', $l->price) }}" placeholder="{{ t('listings.negotiable', 'По договаряне') }}"></label>
            <label>{{ t('listings.f_currency', 'Валута') }}
                <select name="currency">
                    @foreach (['EUR' => '€ EUR', 'BGN' => 'лв. BGN'] as $k => $v)<option value="{{ $k }}" @selected(old('currency', $l->currency) === $k)>{{ $v }}</option>@endforeach
                </select>
            </label>
        </div>
        <small class="muted">{{ t('listings.price_hint', 'Оставете празно за „По договаряне“, 0 = безплатно.') }}</small>

        <label>{{ t('listings.f_city', 'Населено място') }} <input name="city" value="{{ old('city', $l->city) }}" maxlength="80"></label>

        <fieldset class="ls-fieldset">
            <legend>{{ t('listings.contact', 'Контакт') }}</legend>
            <label>{{ t('listings.f_name', 'Име') }} <input name="contact_name" value="{{ old('contact_name', $l->contact_name ?: auth()->user()->name) }}" required maxlength="80"></label>
            <label>{{ t('listings.f_phone', 'Телефон') }} <input type="tel" name="contact_phone" value="{{ old('contact_phone', $l->contact_phone) }}" maxlength="30" autocomplete="tel"></label>
            <label>{{ t('listings.f_email', 'Имейл') }} <input type="email" name="contact_email" value="{{ old('contact_email', $l->contact_email) }}" maxlength="120" autocomplete="email"></label>
            <small class="muted">{{ t('listings.contact_hint', 'Телефонът и/или имейлът ще са видими публично. Нужен е поне един.') }}</small>
        </fieldset>

        <label>{{ t('listings.f_days', 'Валидност') }}
            <select name="days">
                @foreach (\App\Support\Listings::dayOptions() as $d)<option value="{{ $d }}" @selected((int) old('days', $l->days) === $d)>{{ $d }} {{ t('listings.days', 'дни') }}</option>@endforeach
            </select>
        </label>

        @if($max > 0)
        <fieldset class="ls-fieldset">
            <legend>{{ t('listings.photos', 'Снимки') }} <small class="muted">({{ t('listings.photos_max', 'до :n', ['n' => $max]) }})</small></legend>
            @if($images->isNotEmpty())
            <div class="ls-gallery ls-edit">
                @foreach ($images as $m)
                    <label class="ls-thumb"><img src="{{ $m->thumbUrl() }}" alt=""><span class="check"><input type="checkbox" name="remove[]" value="{{ $m->id }}"> {{ t('listings.remove', 'Премахни') }}</span></label>
                @endforeach
            </div>
            @endif
            <input type="file" name="photos[]" accept="image/jpeg,image/png,image/webp" multiple>
        </fieldset>
        @endif

        <button class="btn">{{ $edit ? t('listings.save', 'Запази') : t('listings.submit', 'Изпрати обявата') }}</button>
    </form>
    <p><a href="{{ lroute('listings.mine') }}">← {{ t('listings.mine', 'Моите обяви') }}</a></p>
</article>
@endsection
