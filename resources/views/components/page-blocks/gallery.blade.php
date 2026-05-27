@php
    use App\Cms\PageBlocks\Support\PageBlockRenderer;

    $images = collect(data_get($data, 'images', []))->filter()->values();
    $captions = PageBlockRenderer::captions(data_get($data, 'caption_notes'));
    $layout = in_array(data_get($settings, 'layout'), ['grid', 'masonry', 'carousel-ready'], true) ? data_get($settings, 'layout') : 'grid';
@endphp

@if ($images->isNotEmpty())
    <div class="page-block-gallery page-block-gallery--{{ $layout }}">
        @foreach ($images as $image)
            @php
                $imageUrl = PageBlockRenderer::mediaUrl($image);
                $caption = $captions[$loop->index] ?? '';
            @endphp

            @continue(! $imageUrl)

            <figure class="page-block-gallery-item">
                <img src="{{ $imageUrl }}" alt="{{ $caption }}">
                @if ($caption !== '')
                    <figcaption>{{ $caption }}</figcaption>
                @endif
            </figure>
        @endforeach
    </div>
@endif
