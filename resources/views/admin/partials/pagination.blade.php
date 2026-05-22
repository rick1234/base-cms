@if ($paginator->hasPages())
    <nav class="admin-pagination" aria-label="{{ __('Pagination') }}">
        <span>{{ __('Page :current of :last', ['current' => $paginator->currentPage(), 'last' => $paginator->lastPage()]) }}</span>

        <span class="form-actions">
            @if ($paginator->onFirstPage())
                <span>{{ __('Previous') }}</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev">{{ __('Previous') }}</a>
            @endif

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next">{{ __('Next') }}</a>
            @else
                <span>{{ __('Next') }}</span>
            @endif
        </span>
    </nav>
@endif
