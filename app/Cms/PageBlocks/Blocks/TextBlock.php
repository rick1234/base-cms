<?php

namespace App\Cms\PageBlocks\Blocks;

use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Support\Icons\Heroicon;

class TextBlock extends BasePageBlock
{
    public function key(): string
    {
        return 'text';
    }

    public function label(): string
    {
        return __('Text');
    }

    public function icon(): Heroicon
    {
        return Heroicon::OutlinedBars3BottomLeft;
    }

    public function filamentSchema(): array
    {
        return [
            RichEditor::make('data.content')
                ->label(__('Content'))
                ->toolbarButtons([
                    ['bold', 'italic', 'underline', 'strike', 'link'],
                    ['h2', 'h3', 'paragraph'],
                    ['alignStart', 'alignCenter', 'alignEnd'],
                    ['blockquote', 'bulletList', 'orderedList'],
                    ['table', 'clearFormatting'],
                    ['undo', 'redo'],
                ])
                ->required()
                ->columnSpanFull(),
            Toggle::make('settings.intro_style')
                ->label(__('Intro style')),
            Select::make('settings.alignment')
                ->label(__('Alignment'))
                ->options([
                    'left' => __('Left'),
                    'center' => __('Center'),
                    'right' => __('Right'),
                ])
                ->default('left')
                ->required(),
            Select::make('settings.background_style')
                ->label(__('Background'))
                ->options([
                    'none' => __('None'),
                    'muted' => __('Muted'),
                    'accent' => __('Accent'),
                ])
                ->default('none')
                ->required(),
        ];
    }

    public function defaultSettings(): array
    {
        return [
            'alignment' => 'left',
            'background_style' => 'none',
            'intro_style' => false,
        ];
    }
}
