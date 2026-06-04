<?php

namespace App\Cms\PageBlocks\Blocks;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;

class ImageBlock extends BasePageBlock
{
    public function key(): string
    {
        return 'image';
    }

    public function label(): string
    {
        return __('Image');
    }

    public function icon(): Heroicon
    {
        return Heroicon::OutlinedPhoto;
    }

    public function filamentSchema(): array
    {
        return [
            FileUpload::make('data.image')
                ->label(__('Image'))
                ->disk('public')
                ->directory('content/blocks')
                ->visibility('public')
                ->image()
                ->imageEditor()
                ->imageEditorAspectRatios([
                    null,
                    '16:9',
                    '4:3',
                    '1:1',
                ])
                ->maxSize(10240)
                ->columnSpanFull(),
            TextInput::make('data.alt')
                ->label(__('Alt text'))
                ->maxLength(255),
            TextInput::make('data.caption')
                ->label(__('Caption'))
                ->maxLength(255),
            TextInput::make('data.link_url')
                ->label(__('Link URL'))
                ->regex('/^(https?:\\/\\/|mailto:|tel:|\\/).+/i')
                ->maxLength(255),
            Select::make('settings.layout')
                ->label(__('Image layout'))
                ->options([
                    'default' => __('Default'),
                    'wide' => __('Wide'),
                    'figure' => __('Figure'),
                ])
                ->default('default')
                ->required(),
            Select::make('settings.aspect')
                ->label(__('Aspect ratio'))
                ->options([
                    'auto' => __('Original'),
                    '16-9' => '16:9',
                    '4-3' => '4:3',
                    '1-1' => '1:1',
                ])
                ->default('auto')
                ->required(),
        ];
    }

    public function defaultSettings(): array
    {
        return [
            'layout' => 'default',
            'aspect' => 'auto',
        ];
    }
}
