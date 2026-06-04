<?php

namespace App\Cms\PageBlocks\Blocks;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Support\Icons\Heroicon;

class GalleryBlock extends BasePageBlock
{
    public function key(): string
    {
        return 'gallery';
    }

    public function label(): string
    {
        return __('Gallery');
    }

    public function icon(): Heroicon
    {
        return Heroicon::OutlinedSquares2x2;
    }

    public function filamentSchema(): array
    {
        return [
            FileUpload::make('data.images')
                ->label(__('Images'))
                ->disk('public')
                ->directory('content/block-galleries')
                ->visibility('public')
                ->image()
                ->imageEditor()
                ->multiple()
                ->appendFiles()
                ->reorderable()
                ->maxSize(10240)
                ->columnSpanFull(),
            Select::make('settings.layout')
                ->label(__('Gallery layout'))
                ->options([
                    'grid' => __('Grid'),
                    'masonry' => __('Masonry'),
                    'carousel-ready' => __('Carousel ready'),
                ])
                ->default('grid')
                ->required(),
            Textarea::make('data.caption_notes')
                ->label(__('Caption notes'))
                ->helperText(__('Optional captions, one per line in image order.'))
                ->rows(4)
                ->columnSpanFull(),
        ];
    }

    public function defaultSettings(): array
    {
        return [
            'layout' => 'grid',
        ];
    }
}
