@if ($paginator->hasPages())
    @php
        $currentPage = $paginator->currentPage();
        $lastPage = $paginator->lastPage();
        $startPage = max(1, $currentPage - 1);
        $endPage = min($lastPage, $currentPage + 1);

        if ($currentPage === 1) {
            $endPage = min($lastPage, 3);
        }

        if ($currentPage === $lastPage) {
            $startPage = max(1, $lastPage - 2);
        }
    @endphp

    <nav class="admin-pagination" aria-label="{{ __('Pagination') }}">
        <ul class="admin-pagination-list">
            @for ($page = $startPage; $page <= $endPage; $page++)
                <li>
                    @if ($page === $currentPage)
                        <span class="admin-pagination-current" aria-current="page">{{ $page }}</span>
                    @else
                        <a class="admin-pagination-link" href="{{ $paginator->url($page) }}">{{ $page }}</a>
                    @endif
                </li>
            @endfor

            @if ($endPage < $lastPage)
                @if ($endPage < $lastPage - 1)
                    <li><span class="admin-pagination-gap">&hellip;</span></li>
                @endif
                <li><a class="admin-pagination-link" href="{{ $paginator->url($lastPage) }}">{{ $lastPage }}</a></li>
            @endif

            @if ($paginator->hasMorePages())
                <li><a class="admin-pagination-link" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="{{ __('Next') }}">&rsaquo;</a></li>
                <li><a class="admin-pagination-link" href="{{ $paginator->url($lastPage) }}" aria-label="{{ __('Last page') }}">&rarr;</a></li>
            @else
                <li><span class="admin-pagination-disabled" aria-hidden="true">&rsaquo;</span></li>
                <li><span class="admin-pagination-disabled" aria-hidden="true">&rarr;</span></li>
            @endif
        </ul>
    </nav>
@endif
