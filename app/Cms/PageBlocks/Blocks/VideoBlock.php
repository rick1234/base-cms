<?php

namespace App\Cms\PageBlocks\Blocks;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;

class VideoBlock extends BasePageBlock
{
    public function key(): string
    {
        return 'video';
    }

    public function label(): string
    {
        return __('Video');
    }

    public function icon(): Heroicon
    {
        return Heroicon::OutlinedVideoCamera;
    }

    public function filamentSchema(): array
    {
        return [
            TextInput::make('data.video_url')
                ->label(__('Video URL'))
                ->url()
                ->maxLength(255)
                ->columnSpanFull(),
            Select::make('settings.provider')
                ->label(__('Provider'))
                ->options([
                    'auto' => __('Auto detect'),
                    'youtube' => 'YouTube',
                    'vimeo' => 'Vimeo',
                ])
                ->default('auto')
                ->required(),
            TextInput::make('data.caption')
                ->label(__('Caption'))
                ->maxLength(255),
            FileUpload::make('data.poster')
                ->label(__('Poster image'))
                ->disk('public')
                ->directory('content/blocks/posters')
                ->visibility('public')
                ->image()
                ->imageEditor()
                ->maxSize(10240)
                ->columnSpanFull(),
        ];
    }

    public function defaultSettings(): array
    {
        return [
            'provider' => 'auto',
        ];
    }
}
