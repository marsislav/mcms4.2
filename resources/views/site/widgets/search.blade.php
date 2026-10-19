<form class="widget-search" role="search" method="get" action="{{ lroute('search') }}">
    <input type="search" name="q" placeholder="{{ __(':p1', ['p1' => t('site.search', __('Търсене…'))]) }}" aria-label="{{ __(':p1', ['p1' => t('site.search', __('Търсене'))]) }}" maxlength="100">
    <button type="submit">{{ t('site.search_go', __('Търси')) }}</button>
</form>
