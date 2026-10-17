<form method="post" action="{{ route('admin.settings.footer') }}" class="settings-form">
    @csrf @method('PUT')

    <section class="card">
        <h2>Футър</h2>
        <label>Брой колони
            <select name="footer_cols" data-fcols>
                <option value="0" @selected($footerCols === 0)>Стандартен футър (без колони)</option>
                @for ($i = 1; $i <= \App\Support\Widgets::MAX_COLS; $i++)<option value="{{ $i }}" @selected($footerCols === $i)>{{ $i }}</option>@endfor
            </select>
        </label>
        <p class="muted">Колоните са с равна ширина; на малки екрани се подреждат една под друга. Във всяка колона добавяйте уиджети: текст/HTML (с Font Awesome), меню, последни публикации, категории, тагове и др. Ако колоните са празни, се показва стандартният футър. Текстът „Текст във футъра“ от „Общи“ остава най-долу.</p>
    </section>

    @for ($c = 1; $c <= \App\Support\Widgets::MAX_COLS; $c++)
        <section class="card" data-fcol="{{ $c }}">
            <h3>Колона {{ $c }}</h3>
            <input type="hidden" name="seen[]" value="{{ $c }}">
            @include('admin.partials.widget-area', ['col' => $c, 'list' => $footerAll[$c] ?? []])
        </section>
    @endfor

    <button class="btn primary">Запази футъра</button>
</form>
