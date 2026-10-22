{{-- Newsletter sign-up form. Texts/options: Settings > Newsletter. Needs the `subscribers` table. --}}
@if(\App\Support\Options::on('nl_on') && \App\Support\Tables::has('subscribers'))
<form class="newsletter" method="post" action="{{ lroute('newsletter.subscribe') }}">
    @csrf
    <p class="nl-text">{{ \App\Support\Options::loc('nl_text', __('Получавайте новите статии на имейл.')) }}</p>
    @if(session('newsletter'))<div class="notice ok" role="status">{{ session('newsletter') }}</div>@endif
    @error('nl_email')<div class="notice err" role="alert">{{ $message }}</div>@enderror
    @if(\App\Support\Options::on('nl_ask_name'))
        <input name="nl_name" maxlength="100" placeholder="{{ __('Име') }}" aria-label="{{ __('Име') }}" autocomplete="name">
    @endif
    <input type="email" name="nl_email" required maxlength="190" placeholder="{{ __('Имейл') }}" aria-label="{{ __('Имейл') }}" autocomplete="email" value="{{ old('nl_email') }}">
    <div class="hp" aria-hidden="true"><input type="text" name="website_url" tabindex="-1" autocomplete="off"></div>
    @if(\App\Support\Options::on('nl_require_consent'))
        <label class="check"><input type="checkbox" name="nl_consent" value="1" required> {{ \App\Support\Options::loc('nl_consent', __('Съгласен съм да получавам имейли и мога да се отпиша по всяко време.')) }}</label>
    @endif
    <button class="btn" type="submit">{{ \App\Support\Options::loc('nl_button', __('Абонирай ме')) }}</button>
</form>
@endif
