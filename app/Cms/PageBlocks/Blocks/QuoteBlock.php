<?php

namespace App\Cms\PageBlocks\Blocks;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;

class QuoteBlock extends BasePageBlock
{
    public function key(): string
    {
        return 'quote';
    }

    public function label(): string
    {
        return __('Quote');
    }

    public function icon(): Heroicon
    {
        return Heroicon::OutlinedChatBubbleBottomCenterText;
    }

    public function filamentSchema(): array
    {
        return [
            Textarea::make('data.quote')
                ->label(__('Quote'))
                ->rows(4)
                ->columnSpanFull(),
            TextInput::make('data.author')
                ->label(__('Author'))
                ->maxLength(255),
            TextInput::make('data.source')
                ->label(__('Source'))
                ->maxLength(255),
            Select::make('settings.style')
                ->label(__('Style'))
                ->options([
                    'default' => __('Default'),
                    'highlight' => __('Highlight'),
                    'minimal' => __('Minimal'),
                ])
                ->default('default')
                ->required(),
        ];
    }

    public function defaultSettings(): array
    {
        return [
            'style' => 'default',
        ];
    }
}
