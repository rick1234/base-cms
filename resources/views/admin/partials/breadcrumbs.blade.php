@if ($breadcrumbs->isNotEmpty())
    <nav class="breadcrumbs" aria-label="{{ __('Breadcrumbs') }}">
        @foreach ($breadcrumbs as $breadcrumb)
            @if (! $loop->first)
                <span class="breadcrumbs-separator" aria-hidden="true">&rsaquo;</span>
            @endif

            @if ($breadcrumb['url'] && ! $loop->last)
                <a href="{{ $breadcrumb['url'] }}">{{ $breadcrumb['label'] }}</a>
            @else
                <span @if ($loop->last) aria-current="page" @endif>{{ $breadcrumb['label'] }}</span>
            @endif
        @endforeach
    </nav>
@endif
