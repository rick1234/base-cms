<?php

namespace App\Cms\PageBlocks;

use App\Cms\PageBlocks\Contracts\PageBlock;
use InvalidArgumentException;

class PageBlockRegistry
{
    /**
     * @var array<string, PageBlock>
     */
    private array $blocks = [];

    public function register(PageBlock|string $block): self
    {
        $instance = is_string($block) ? app($block) : $block;

        if (! $instance instanceof PageBlock) {
            throw new InvalidArgumentException('Registered page blocks must implement '.PageBlock::class.'.');
        }

        $this->blocks[$instance->key()] = $instance;

        return $this;
    }

    /**
     * @return array<string, PageBlock>
     */
    public function all(): array
    {
        return $this->blocks;
    }

    public function get(string $key): ?PageBlock
    {
        return $this->blocks[$key] ?? null;
    }

    /**
     * @return list<string>
     */
    public function keys(): array
    {
        return array_keys($this->blocks);
    }
}
