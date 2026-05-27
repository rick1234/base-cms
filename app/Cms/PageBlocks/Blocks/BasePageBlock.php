<?php

namespace App\Cms\PageBlocks\Blocks;

use App\Cms\PageBlocks\Contracts\PageBlock;

abstract class BasePageBlock implements PageBlock
{
    /**
     * @return array<string, mixed>
     */
    public function defaultData(): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    public function defaultSettings(): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    public function validationRules(): array
    {
        return [];
    }

    public function previewView(): string
    {
        return 'admin.content.page-block-previews.'.$this->key();
    }

    public function frontendView(): string
    {
        return 'components.page-blocks.'.$this->key();
    }
}
