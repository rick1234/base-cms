@php
    use App\Cms\PageBlocks\Support\PageBlockRenderer;

    $embedUrl = PageBlockRenderer::videoEmbedUrl(data_get($data, 'video_url'), data_get($settings, 'provider'));
    $caption = (string) data_get($data, 'caption', '');
@endphp

@if ($embedUrl)
    <figure class="page-block-video">
        <div class="page-block-video-frame">
            <iframe
                src="{{ $embedUrl }}"
                title="{{ $caption !== '' ? $caption : __('Video') }}"
                loading="lazy"
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                allowfullscreen
            ></iframe>
        </div>

        @if ($caption !== '')
            <figcaption>{{ $caption }}</figcaption>
        @endif
    </figure>
@endif
