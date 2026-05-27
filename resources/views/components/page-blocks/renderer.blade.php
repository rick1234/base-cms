@props(['blocks' => []])

@php
    use App\Cms\PageBlocks\PageBlockRegistry;
    use App\Cms\PageBlocks\Support\PageBlockRenderer;

    $registry = app(PageBlockRegistry::class);
@endphp

@if (filled($blocks))
    <div class="page-block-grid">
        @foreach ($blocks as $block)
            @php
                $type = (string) data_get($block, 'type');
                $definition = $registry->get($type);
                $width = PageBlockRenderer::blockWidth(data_get($block, 'layout', '100'));
            @endphp

            @continue(! $definition)

            <div class="page-block page-block--{{ str($type)->kebab() }} page-block--width-{{ $width }}">
                @include($definition->frontendView(), [
                    'block' => $block,
                    'data' => data_get($block, 'data', []),
                    'settings' => data_get($block, 'settings', []),
                ])
            </div>
        @endforeach
    </div>
@endif
