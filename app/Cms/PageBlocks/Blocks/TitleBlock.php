<?php

namespace App\Cms\PageBlocks\Blocks;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;

class TitleBlock extends BasePageBlock
{
    public function key(): string
    {
        return 'title';
    }

    public function label(): string
    {
        return __('Title');
    }

    public function icon(): Heroicon
    {
        return Heroicon::OutlinedH2;
    }

    public function filamentSchema(): array
    {
        return [
            TextInput::make('data.title')
                ->label(__('Title'))
                ->required()
                ->maxLength(255)
                ->live(onBlur: true)
                ->columnSpanFull(),
            Select::make('data.level')
                ->label(__('Level'))
                ->options([
                    'h2' => 'H2',
                    'h3' => 'H3',
                    'h4' => 'H4',
                    'h5' => 'H5',
                    'h6' => 'H6',
                ])
                ->default('h2')
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
            TextInput::make('settings.anchor')
                ->label(__('Anchor ID'))
                ->maxLength(80)
                ->regex('/^[A-Za-z][A-Za-z0-9\\-_:.]*$/')
                ->helperText(__('Optional anchor without #.')),
        ];
    }

    public function defaultData(): array
    {
        return [
            'level' => 'h2',
        ];
    }

    public function defaultSettings(): array
    {
        return [
            'alignment' => 'left',
        ];
    }
}
