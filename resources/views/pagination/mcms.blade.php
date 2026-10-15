@if ($paginator->hasPages())
<nav class="pager" role="navigation" aria-label="Pagination">
    @if ($paginator->onFirstPage())
        <span class="disabled">{{ t('pagination.prev', '← Previous') }}</span>
    @else
        <a href="{{ $paginator->previousPageUrl() }}" rel="prev">{{ t('pagination.prev', '← Previous') }}</a>
    @endif

    <span class="count">{{ $paginator->currentPage() }} / {{ method_exists($paginator, 'lastPage') ? $paginator->lastPage() : '…' }}</span>

    @if ($paginator->hasMorePages())
        <a href="{{ $paginator->nextPageUrl() }}" rel="next">{{ t('pagination.next', 'Next →') }}</a>
    @else
        <span class="disabled">{{ t('pagination.next', 'Next →') }}</span>
    @endif
</nav>
@endif
