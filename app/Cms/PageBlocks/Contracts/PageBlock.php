<?php

namespace App\Cms\PageBlocks\Contracts;

use BackedEnum;
use Illuminate\Contracts\Support\Htmlable;

interface PageBlock
{
    public function key(): string;

    public function label(): string;

    public function icon(): string|BackedEnum|Htmlable|null;

    /**
     * @return array<int, mixed>
     */
    public function filamentSchema(): array;

    public function previewView(): string;

    public function frontendView(): string;

    /**
     * @return array<string, mixed>
     */
    public function defaultData(): array;

    /**
     * @return array<string, mixed>
     */
    public function defaultSettings(): array;

    /**
     * @return array<string, mixed>
     */
    public function validationRules(): array;
}
