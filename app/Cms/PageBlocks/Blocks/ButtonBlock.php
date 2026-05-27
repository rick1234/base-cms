<?php

namespace App\Cms\PageBlocks\Blocks;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Support\Icons\Heroicon;

class ButtonBlock extends BasePageBlock
{
    public function key(): string
    {
        return 'button';
    }

    public function label(): string
    {
        return __('Button');
    }

    public function icon(): Heroicon
    {
        return Heroicon::OutlinedCursorArrowRays;
    }

    public function filamentSchema(): array
    {
        return [
            TextInput::make('data.label')
                ->label(__('Label'))
                ->required()
                ->maxLength(80),
            TextInput::make('data.url')
                ->label(__('URL'))
                ->required()
                ->regex('/^(https?:\\/\\/|mailto:|tel:|\\/).+/i')
                ->maxLength(255),
            Select::make('settings.style')
                ->label(__('Style'))
                ->options([
                    'primary' => __('Primary'),
                    'secondary' => __('Secondary'),
                    'text' => __('Text'),
                ])
                ->default('primary')
                ->required(),
            Select::make('settings.alignment')
                ->label(__('Alignment'))
                ->options([
                    'left' => __('Left'),
                    'center' => __('Center'),
                    'right' => __('Right'),
                ])
                ->default('left')
                ->required(),
            Toggle::make('settings.open_in_new_tab')
                ->label(__('Open in new tab')),
        ];
    }

    public function defaultSettings(): array
    {
        return [
            'style' => 'primary',
            'alignment' => 'left',
            'open_in_new_tab' => false,
        ];
    }
}
