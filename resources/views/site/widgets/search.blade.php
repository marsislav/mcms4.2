<form class="widget-search" role="search" method="get" action="{{ lroute('search') }}">
    <input type="search" name="q" placeholder="{{ t('site.search', 'Търсене…') }}" aria-label="{{ t('site.search', 'Търсене') }}" maxlength="100">
    <button type="submit">{{ t('site.search_go', 'Търси') }}</button>
</form>
