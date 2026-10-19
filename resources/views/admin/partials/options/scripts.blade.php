<p class="muted">{{ __('Код, който се вмъква във всички страници на публичния сайт (не и в панела) — Google Search Console meta таг, Analytics и др. Скриптовете автоматично получават CSP nonce.') }}</p>
<label>{!! __('В &lt;head&gt;') !!} <textarea name="scripts_head" rows="6" maxlength="20000" spellcheck="false" class="code">{{ $opt['scripts_head'] }}</textarea></label>
<label>{!! __('Преди &lt;/body&gt;') !!} <textarea name="scripts_footer" rows="6" maxlength="20000" spellcheck="false" class="code">{{ $opt['scripts_footer'] }}</textarea></label>
<label>{!! __('Разрешени външни адреса за скриптове и заявки (CSP) <small class="muted">по един на ред, само https</small>') !!}
    <textarea name="csp_hosts" rows="4" maxlength="1500" spellcheck="false" class="code" placeholder="https://www.googletagmanager.com&#10;https://www.google-analytics.com">{{ $opt['csp_hosts'] }}</textarea>
</label>
<p class="muted">{{ __('Политиката за сигурност (CSP) блокира външни скриптове, освен ако адресът им не е в списъка по-горе. Вграден (inline) код работи, външен — само от изброените адреса. Добавяйте само услуги, на които имате доверие. Този таб е достъпен само за администратори.') }}</p>

<label>{!! __('Собствен CSS <small class="muted">(добавя се към /design.css; без &lt;style&gt; тагове и @import)</small>') !!}
    <textarea name="custom_css" rows="8" maxlength="20000" spellcheck="false" class="code" placeholder=".entry h1 { letter-spacing: -.02em; }">{{ $opt['custom_css'] }}</textarea>
</label>
