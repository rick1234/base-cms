@php
    $quote = (string) data_get($data, 'quote', '');
    $author = (string) data_get($data, 'author', '');
    $source = (string) data_get($data, 'source', '');
    $style = in_array(data_get($settings, 'style'), ['default', 'highlight', 'minimal'], true) ? data_get($settings, 'style') : 'default';
@endphp

@if ($quote !== '')
    <figure class="page-block-quote page-block-quote--{{ $style }}">
        <blockquote>
            <p>{{ $quote }}</p>
        </blockquote>

        @if ($author !== '' || $source !== '')
            <figcaption>
                @if ($author !== '')
                    <span>{{ $author }}</span>
                @endif
                @if ($source !== '')
                    <cite>{{ $source }}</cite>
                @endif
            </figcaption>
        @endif
    </figure>
@endif
