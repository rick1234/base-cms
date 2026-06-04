@php
    $categoryToolbarLink = app(\App\Support\Admin\Categories\CategoryToolbarLink::class)->forRequest(request());
@endphp

@if ($categoryToolbarLink)
    <a class="btn category-related-items-button" href="{{ $categoryToolbarLink['url'] }}">
        <x-admin.material-icon name="list_alt" />
        {{ $categoryToolbarLink['label'] }}
    </a>
@endif
