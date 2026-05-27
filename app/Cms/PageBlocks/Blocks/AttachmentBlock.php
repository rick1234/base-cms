<?php

namespace App\Cms\PageBlocks\Blocks;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Support\Icons\Heroicon;

class AttachmentBlock extends BasePageBlock
{
    public function key(): string
    {
        return 'attachment';
    }

    public function label(): string
    {
        return __('Attachment');
    }

    public function icon(): Heroicon
    {
        return Heroicon::OutlinedPaperClip;
    }

    public function filamentSchema(): array
    {
        return [
            FileUpload::make('data.file')
                ->label(__('File'))
                ->disk('public')
                ->directory('content/block-attachments')
                ->visibility('public')
                ->maxSize(20480)
                ->downloadable()
                ->openable()
                ->required()
                ->columnSpanFull(),
            TextInput::make('data.display_title')
                ->label(__('Display title'))
                ->maxLength(255),
            TextInput::make('data.button_label')
                ->label(__('Button label'))
                ->default(__('Download'))
                ->maxLength(80),
            Textarea::make('data.description')
                ->label(__('Description'))
                ->rows(3)
                ->columnSpanFull(),
            Toggle::make('settings.open_in_new_tab')
                ->label(__('Open in new tab')),
        ];
    }

    public function defaultData(): array
    {
        return [
            'button_label' => __('Download'),
        ];
    }

    public function defaultSettings(): array
    {
        return [
            'open_in_new_tab' => false,
        ];
    }
}
