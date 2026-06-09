@php
    use App\Cms\PageBlocks\Support\PageBlockRenderer;
    use App\Support\Media\HandledImage;

    $images = collect(data_get($data, 'images', []))->filter()->values();
    $captions = PageBlockRenderer::captions(data_get($data, 'caption_notes'));
    $layout = in_array(data_get($settings, 'layout'), ['grid', 'masonry', 'carousel-ready'], true) ? data_get($settings, 'layout') : 'grid';
@endphp

@if ($images->isNotEmpty())
    <div class="page-block-gallery page-block-gallery--{{ $layout }}">
        @foreach ($images as $image)
            @php
                $caption = $captions[$loop->index] ?? '';
                $handledImage = HandledImage::fromPath(is_string($image) ? $image : null, $caption);
                $imageUrl = $handledImage->handle(520, 390, true);
            @endphp

            @continue(! $imageUrl)

            <figure class="page-block-gallery-item">
                <x-media.image :image="$handledImage" :alt="$caption" :width="520" :height="390" crop group="page-block-gallery" />
                @if ($caption !== '')
                    <figcaption>{{ $caption }}</figcaption>
                @endif
            </figure>
        @endforeach
    </div>
@endif
