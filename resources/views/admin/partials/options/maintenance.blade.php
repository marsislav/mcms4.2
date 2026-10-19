<p>{!! __('Режимът „профилактика“ се включва в таб „Общи“ (в момента: <strong>:p1</strong>). Влезлите в панела потребители винаги виждат сайта.', ['p1' => e(setting('maintenance_mode') === '1' ? __('ВКЛЮЧЕН') : __('изключен'))]) !!}</p>
<label>{{ __('Съобщение към посетителите') }} <textarea name="maintenance_message" rows="3" maxlength="500">{{ $opt['maintenance_message'] }}</textarea></label>
<label>{!! __('IP адреса, които винаги виждат сайта <small class="muted">по един на ред</small>') !!}
    <textarea name="maintenance_ips" rows="3" maxlength="800" spellcheck="false" class="code" placeholder="{{ __('Вашият IP: :p1', ['p1' => request()->ip()]) }}">{{ $opt['maintenance_ips'] }}</textarea>
</label>
