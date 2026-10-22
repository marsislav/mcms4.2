@php
    $cfRows = array_values($contactFields ?? []);
    $blank = ['key' => '', 'type' => 'text', 'label' => []];
@endphp
<form method="post" action="{{ route('admin.settings.contact') }}" class="settings-form" data-cf-form>
    @csrf @method('PUT')
    <section class="card">
        <h2>{{ __('Контактна форма') }}</h2>
        <p class="muted">{{ __('Имейл, име, тема и съобщение са винаги налични. Тук добавяте свои полета: разместете ги със стрелките, променете типа, ширината, примерния текст, помощния текст, стойността по подразбиране, ограниченията и CSS класа. Полетата се показват преди съобщението и се добавят към текста му (в панела и в писмото до вас).') }}</p>
        @error('fields')<div class="flash err">{{ $message }}</div>@enderror

        <div data-cf-list>
            @foreach ($cfRows as $i => $f)
                @include('admin.partials.contact-field-row', ['i' => $i, 'f' => $f])
            @endforeach
        </div>
        <template data-cf-tpl>@include('admin.partials.contact-field-row', ['i' => '__I__', 'f' => $blank, 'open' => true])</template>
        <p><button type="button" class="btn" data-cf-add>＋ {{ __('Добави поле') }}</button></p>
    </section>
    <button class="btn primary">{{ __('Запази полетата') }}</button>
</form>
