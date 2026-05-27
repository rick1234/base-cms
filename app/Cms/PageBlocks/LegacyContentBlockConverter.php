<?php

namespace App\Cms\PageBlocks;

use App\Models\Cms\ContentBlock;
use App\Models\Cms\ContentBlockPart;
use App\Models\Cms\ContentItem;
use Illuminate\Support\Str;

class LegacyContentBlockConverter
{
    /**
     * @return list<array{type: string, uuid: string, layout: string, data: array<string, mixed>, settings: array<string, mixed>}>
     */
    public function convert(ContentItem $contentItem): array
    {
        $contentItem->loadMissing('blocks.layout', 'blocks.containers.parts');

        $blocks = [];

        foreach ($contentItem->blocks as $legacyBlock) {
            $layout = $this->layoutForLegacyBlock($legacyBlock);

            foreach ($legacyBlock->containers as $container) {
                foreach ($container->parts as $part) {
                    $converted = $this->convertPart($part, $layout);

                    if ($converted !== null) {
                        $blocks[] = $converted;
                    }
                }
            }
        }

        return $blocks;
    }

    /**
     * @return array<string, mixed>
     */
    public function snapshot(ContentItem $contentItem): array
    {
        $contentItem->loadMissing('blocks.layout', 'blocks.containers.parts');

        return $contentItem->blocks
            ->map(fn (ContentBlock $block): array => [
                'id' => $block->id,
                'name' => $block->name,
                'layout' => $block->layout?->only(['id', 'name', 'handle', 'columns']),
                'sort_order' => $block->sort_order,
                'configuration' => $block->configuration,
                'containers' => $block->containers->map(fn ($container): array => [
                    'id' => $container->id,
                    'region' => $container->region,
                    'sort_order' => $container->sort_order,
                    'parts' => $container->parts->map(fn (ContentBlockPart $part): array => [
                        'id' => $part->id,
                        'type' => $part->type,
                        'title' => $part->title,
                        'content' => $part->content,
                        'sort_order' => $part->sort_order,
                        'settings' => $part->settings,
                    ])->values()->all(),
                ])->values()->all(),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array{type: string, uuid: string, layout: string, data: array<string, mixed>, settings: array<string, mixed>}|null
     */
    private function convertPart(ContentBlockPart $part, string $layout): ?array
    {
        $type = (string) $part->type;
        $content = is_string($part->content) ? trim($part->content) : '';

        if ($content === '' && blank($part->title)) {
            return null;
        }

        return match ($type) {
            'text' => [
                'type' => 'text',
                'uuid' => (string) Str::uuid(),
                'layout' => $layout,
                'data' => [
                    'content' => $content,
                ],
                'settings' => [
                    'alignment' => 'left',
                    'background_style' => 'none',
                    'intro_style' => false,
                ],
            ],
            'img' => [
                'type' => 'image',
                'uuid' => (string) Str::uuid(),
                'layout' => $layout,
                'data' => [
                    'image' => Str::after($content, 'storage/'),
                    'alt' => data_get($part->settings, 'alt_text') ?: $part->title,
                    'caption' => $part->title,
                ],
                'settings' => [
                    'layout' => 'default',
                    'aspect' => 'auto',
                ],
            ],
            'youtube' => [
                'type' => 'video',
                'uuid' => (string) Str::uuid(),
                'layout' => $layout,
                'data' => [
                    'video_url' => $this->youtubeUrl($content),
                    'caption' => $part->title,
                ],
                'settings' => [
                    'provider' => 'youtube',
                ],
            ],
            default => null,
        };
    }

    private function layoutForLegacyBlock(ContentBlock $block): string
    {
        $columns = count($block->layout?->columns ?? []);

        if ($columns === 0) {
            $columns = max(1, $block->containers->count());
        }

        return match (min($columns, 4)) {
            2 => '50',
            3 => '35',
            4 => '25',
            default => '100',
        };
    }

    private function youtubeUrl(string $content): string
    {
        if (Str::startsWith($content, ['http://', 'https://'])) {
            return $content;
        }

        return 'https://www.youtube.com/watch?v='.$content;
    }
}
