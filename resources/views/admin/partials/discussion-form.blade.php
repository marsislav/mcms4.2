@php
    $dv = fn ($k) => old($k, $discussion[$k] ?? '');
    $chk = fn ($k) => $dv($k) === '1';
@endphp
@unless(\App\Support\Discussion::ready())
<section class="card warn-card">
    {!! __('<strong>Таблицата за коментари още не е създадена.</strong> Натиснете „Обнови базата“ (Табло) или изпълнете <code>database/upgrade/4.2-upgrade.sql</code> в phpMyAdmin.') !!}
    @if(auth()->user()->isAdmin())
    <form method="post" action="{{ route('admin.system.migrate') }}" class="inline-form" style="margin-top:.6rem">@csrf <button class="btn primary">{{ __('Обнови базата') }}</button></form>
    @endif
</section>
@endunless

<form method="post" action="{{ route('admin.settings.discussion') }}" class="settings-form">
    @csrf @method('PUT')

    <section class="card">
        <h2>{{ __('Коментари') }}</h2>
        <label class="check"><input type="checkbox" name="enabled" value="1" @checked($chk('enabled'))> {!! __('<strong>Коментарите са включени</strong> <small class="muted">(изключено = не се показват нито формата, нито вече написаните коментари)</small>') !!}</label>
    </section>

    <section class="card">
        <h2>{{ __('Настройки по подразбиране') }}</h2>
        <label class="check"><input type="checkbox" name="on_posts" value="1" @checked($chk('on_posts'))> {{ __('Позволи коментари под новите публикации') }}</label>
        <label class="check"><input type="checkbox" name="on_pages" value="1" @checked($chk('on_pages'))> {{ __('Позволи коментари под новите страници') }}</label>
        <p class="muted">{{ __('Всяка публикация/страница може да го променя поотделно (кутия „Дискусия“ в редактора). Тези настройки важат, когато там е избрано „По подразбиране“.') }}</p>
        <label class="check"><input type="checkbox" name="require_name_email" value="1" @checked($chk('require_name_email'))> {{ __('Авторът трябва да въведе име и имейл') }}</label>
        <label class="check"><input type="checkbox" name="allow_url" value="1" @checked($chk('allow_url'))> {{ __('Покажи поле „Уебсайт“') }}</label>
        <label class="check"><input type="checkbox" name="show_avatars" value="1" @checked($chk('show_avatars'))> {!! __('Показвай аватари (Gravatar) <small class="muted">— при включване хешът на имейла се изпраща към gravatar.com; по подразбиране е изключено заради поверителността</small>') !!}</label>
    </section>

    <section class="card">
        <h2>{{ __('Вид на коментарите') }}</h2>
        <div class="grid-form">
            <label>{!! __('Автоматично затваряй коментарите на публикации по-стари от (дни) <small class="muted">0 = никога</small>') !!}
                <input type="number" name="close_after_days" min="0" max="3650" value="{{ $dv('close_after_days') }}" required></label>
            <label>{!! __('Коментари на страница (най-горно ниво) <small class="muted">0 = всички на една страница</small>') !!}
                <input type="number" name="per_page" min="0" max="200" value="{{ $dv('per_page') }}" required></label>
            <label>{{ __('Ред на показване') }}
                <select name="order">
                    <option value="asc" @selected($dv('order') === 'asc')>{{ __('Първо най-старите') }}</option>
                    <option value="desc" @selected($dv('order') === 'desc')>{{ __('Първо най-новите') }}</option>
                </select>
            </label>
            <label>{{ __('Нива на отговори (вложени коментари)') }}
                <input type="number" name="thread_depth" min="2" max="5" value="{{ $dv('thread_depth') }}" required></label>
        </div>
        <label class="check"><input type="checkbox" name="threaded" value="1" @checked($chk('threaded'))> {{ __('Позволи вложени коментари (отговори)') }}</label>
    </section>

    <section class="card">
        <h2>{{ __('Имейл известия') }}</h2>
        <p class="muted">{{ __('Получател: имейлът за контакт от „Общи“ (иначе първият администратор).') }}</p>
        <label class="check"><input type="checkbox" name="notify_new" value="1" @checked($chk('notify_new'))> {{ __('Когато някой публикува коментар') }}</label>
        <label class="check"><input type="checkbox" name="notify_hold" value="1" @checked($chk('notify_hold'))> {{ __('Когато коментар чака одобрение') }}</label>
    </section>

    <section class="card">
        <h2>{{ __('Преди да се появи коментар') }}</h2>
        <label class="check"><input type="checkbox" name="moderation_all" value="1" @checked($chk('moderation_all'))> {{ __('Всеки коментар се одобрява ръчно') }}</label>
        <label class="check"><input type="checkbox" name="previously_approved" value="1" @checked($chk('previously_approved'))> {{ __('Авторът трябва да има вече одобрен коментар (същото име и имейл)') }}</label>
    </section>

    <section class="card">
        <h2>{{ __('Модерация и спам') }}</h2>
        <div class="grid-form">
            <label>{!! __('Задръж за одобрение, ако съдържа поне толкова връзки <small class="muted">0 = изключено</small>') !!}
                <input type="number" name="max_links" min="0" max="20" value="{{ $dv('max_links') }}" required></label>
            <label>{!! __('Минимум секунди между коментари от един IP <small class="muted">0 = изключено</small>') !!}
                <input type="number" name="flood_seconds" min="0" max="3600" value="{{ $dv('flood_seconds') }}" required></label>
        </div>
        <label>{!! __('Ключови думи за модерация <small class="muted">(по една на ред; ако са в текста, името, имейла, адреса или IP — коментарът се задържа)</small>') !!}
            <textarea name="moderation_keys" rows="4" maxlength="5000">{{ $dv('moderation_keys') }}</textarea></label>
        <label>{!! __('Забранени думи <small class="muted">(по една на ред; коментарът отива директно в „Спам“)</small>') !!}
            <textarea name="disallowed_keys" rows="4" maxlength="5000">{{ $dv('disallowed_keys') }}</textarea></label>
        <p class="muted">{{ __('Вградена защита: скрито поле-капан, проверка на времето, ограничение на опитите, откриване на дубликати.') }}</p>
    </section>

    <button class="btn primary">{{ __('Запази') }}</button>
</form>
