<form class="widget-search" role="search" method="get" action="{{ lroute('search') }}">
    <input type="search" name="q" @if(\App\Support\Options::on('livesearch')) data-live="{{ lroute('search.live') }}" data-live-min="{{ (int) \App\Support\Options::get('live_min') }}" @endif placeholder="{{ __(':p1', ['p1' => t('site.search', __('Търсене…'))]) }}" aria-label="{{ __(':p1', ['p1' => t('site.search', __('Търсене'))]) }}" maxlength="100">
    <button type="submit">{{ t('site.search_go', __('Търси')) }}</button>
</form>
