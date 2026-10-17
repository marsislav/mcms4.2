@php
    $dv = fn ($k) => old($k, $discussion[$k] ?? '');
    $chk = fn ($k) => $dv($k) === '1';
@endphp
@unless(\App\Support\Discussion::ready())
<section class="card warn-card">
    <strong>Таблицата за коментари още не е създадена.</strong> Натиснете „Обнови базата“ (Табло) или изпълнете <code>database/upgrade/4.2-upgrade.sql</code> в phpMyAdmin.
    @if(auth()->user()->isAdmin())
    <form method="post" action="{{ route('admin.system.migrate') }}" class="inline-form" style="margin-top:.6rem">@csrf <button class="btn primary">Обнови базата</button></form>
    @endif
</section>
@endunless

<form method="post" action="{{ route('admin.settings.discussion') }}" class="settings-form">
    @csrf @method('PUT')

    <section class="card">
        <h2>Коментари</h2>
        <label class="check"><input type="checkbox" name="enabled" value="1" @checked($chk('enabled'))> <strong>Коментарите са включени</strong> <small class="muted">(изключено = не се показват нито формата, нито вече написаните коментари)</small></label>
    </section>

    <section class="card">
        <h2>Настройки по подразбиране</h2>
        <label class="check"><input type="checkbox" name="on_posts" value="1" @checked($chk('on_posts'))> Позволи коментари под новите публикации</label>
        <label class="check"><input type="checkbox" name="on_pages" value="1" @checked($chk('on_pages'))> Позволи коментари под новите страници</label>
        <p class="muted">Всяка публикация/страница може да го променя поотделно (кутия „Дискусия“ в редактора). Тези настройки важат, когато там е избрано „По подразбиране“.</p>
        <label class="check"><input type="checkbox" name="require_name_email" value="1" @checked($chk('require_name_email'))> Авторът трябва да въведе име и имейл</label>
        <label class="check"><input type="checkbox" name="allow_url" value="1" @checked($chk('allow_url'))> Покажи поле „Уебсайт“</label>
        <label class="check"><input type="checkbox" name="show_avatars" value="1" @checked($chk('show_avatars'))> Показвай аватари (Gravatar) <small class="muted">— при включване хешът на имейла се изпраща към gravatar.com; по подразбиране е изключено заради поверителността</small></label>
    </section>

    <section class="card">
        <h2>Вид на коментарите</h2>
        <div class="grid-form">
            <label>Автоматично затваряй коментарите на публикации по-стари от (дни) <small class="muted">0 = никога</small>
                <input type="number" name="close_after_days" min="0" max="3650" value="{{ $dv('close_after_days') }}" required></label>
            <label>Коментари на страница (най-горно ниво) <small class="muted">0 = всички на една страница</small>
                <input type="number" name="per_page" min="0" max="200" value="{{ $dv('per_page') }}" required></label>
            <label>Ред на показване
                <select name="order">
                    <option value="asc" @selected($dv('order') === 'asc')>Първо най-старите</option>
                    <option value="desc" @selected($dv('order') === 'desc')>Първо най-новите</option>
                </select>
            </label>
            <label>Нива на отговори (вложени коментари)
                <input type="number" name="thread_depth" min="2" max="5" value="{{ $dv('thread_depth') }}" required></label>
        </div>
        <label class="check"><input type="checkbox" name="threaded" value="1" @checked($chk('threaded'))> Позволи вложени коментари (отговори)</label>
    </section>

    <section class="card">
        <h2>Имейл известия</h2>
        <p class="muted">Получател: имейлът за контакт от „Общи“ (иначе първият администратор).</p>
        <label class="check"><input type="checkbox" name="notify_new" value="1" @checked($chk('notify_new'))> Когато някой публикува коментар</label>
        <label class="check"><input type="checkbox" name="notify_hold" value="1" @checked($chk('notify_hold'))> Когато коментар чака одобрение</label>
    </section>

    <section class="card">
        <h2>Преди да се появи коментар</h2>
        <label class="check"><input type="checkbox" name="moderation_all" value="1" @checked($chk('moderation_all'))> Всеки коментар се одобрява ръчно</label>
        <label class="check"><input type="checkbox" name="previously_approved" value="1" @checked($chk('previously_approved'))> Авторът трябва да има вече одобрен коментар (същото име и имейл)</label>
    </section>

    <section class="card">
        <h2>Модерация и спам</h2>
        <div class="grid-form">
            <label>Задръж за одобрение, ако съдържа поне толкова връзки <small class="muted">0 = изключено</small>
                <input type="number" name="max_links" min="0" max="20" value="{{ $dv('max_links') }}" required></label>
            <label>Минимум секунди между коментари от един IP <small class="muted">0 = изключено</small>
                <input type="number" name="flood_seconds" min="0" max="3600" value="{{ $dv('flood_seconds') }}" required></label>
        </div>
        <label>Ключови думи за модерация <small class="muted">(по една на ред; ако са в текста, името, имейла, адреса или IP — коментарът се задържа)</small>
            <textarea name="moderation_keys" rows="4" maxlength="5000">{{ $dv('moderation_keys') }}</textarea></label>
        <label>Забранени думи <small class="muted">(по една на ред; коментарът отива директно в „Спам“)</small>
            <textarea name="disallowed_keys" rows="4" maxlength="5000">{{ $dv('disallowed_keys') }}</textarea></label>
        <p class="muted">Вградена защита: скрито поле-капан, проверка на времето, ограничение на опитите, откриване на дубликати.</p>
    </section>

    <button class="btn primary">Запази</button>
</form>
