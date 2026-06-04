@props(['blocks' => []])

@php
    use App\Cms\PageBlocks\PageBlockRegistry;
    use App\Cms\PageBlocks\Support\PageBlockRenderer;

    $registry = app(PageBlockRegistry::class);
    $renderableBlocks = collect($blocks)
        ->filter(function (mixed $block) use ($registry): bool {
            $type = (string) data_get($block, 'type');

            return $registry->get($type) !== null && PageBlockRenderer::hasRenderableContent($block);
        })
        ->values();
@endphp

@if ($renderableBlocks->isNotEmpty())
    <div class="page-block-grid">
        @foreach ($renderableBlocks as $block)
            @php
                $type = (string) data_get($block, 'type');
                $definition = $registry->get($type);
                $width = PageBlockRenderer::blockWidth(data_get($block, 'layout', '100'));
            @endphp

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
