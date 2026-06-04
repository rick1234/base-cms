<?php

namespace App\Livewire\Admin\Content;

use App\Cms\PageBlocks\Contracts\PageBlock;
use App\Cms\PageBlocks\LegacyContentBlockConverter;
use App\Cms\PageBlocks\PageBlockRegistry;
use App\Models\Cms\ContentItem;
use App\Models\Cms\Event;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\Builder\Block as BuilderBlock;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Components\View as SchemaView;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Livewire\Component;

class ContentBlockEditor extends Component implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    public ?int $contentItemId = null;

    public ?int $eventId = null;

    public string $ownerType = 'content';

    /**
     * @var array{blocks: array<int, mixed>}
     */
    public array $data = [
        'blocks' => [],
    ];

    public ?string $message = null;

    public bool $usesLegacySource = false;

    public function mount(?int $contentItemId = null, ?int $eventId = null, string $ownerType = 'content'): void
    {
        $this->ensureAuthorized();

        $this->contentItemId = $contentItemId;
        $this->eventId = $eventId;
        $this->ownerType = $eventId !== null ? 'event' : $ownerType;
        $record = $this->record();
        $blocks = $record->structured_blocks ?? [];

        if ($this->isContentOwner() && $blocks === [] && $this->contentItem()->blocks()->exists()) {
            $contentItem = $this->contentItem();
            $blocks = app(LegacyContentBlockConverter::class)->convert($contentItem);
            $this->usesLegacySource = $blocks !== [];
        }

        $this->form->fill([
            'blocks' => $this->toBuilderState($blocks),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Builder::make('blocks')
                    ->label(__('Content blocks'))
                    ->blocks($this->filamentBlocks())
                    ->blockIcons()
                    ->blockNumbers(false)
                    ->collapsible()
                    ->cloneable()
                    ->reorderableWithButtons()
                    ->reorderableWithDragAndDrop()
                    ->addActionLabel(__('Add block'))
                    ->addAction(fn (Action $action): Action => $this->localizeDirectAddAction($action, __('Add block')))
                    ->addBetweenActionLabel(__('Insert block'))
                    ->addBetweenAction(fn (Action $action): Action => $this->localizeDirectAddAction($action, __('Insert block')))
                    ->cloneAction(fn (Action $action): Action => $action->label(__('Duplicate')))
                    ->reorderAction(fn (Action $action): Action => $action
                        ->label(__('Drag block'))
                        ->icon(Heroicon::OutlinedArrowsPointingOut))
                    ->editAction(fn (Action $action): Action => $action
                        ->label(__('Edit'))
                        ->icon(Heroicon::OutlinedPencilSquare)
                        ->modalHeading(__('Edit block'))
                        ->modalSubmitActionLabel(__('Save changes'))
                        ->modalCancelActionLabel(__('Cancel')))
                    ->deleteAction(fn (Action $action): Action => $action
                        ->label(__('Delete'))
                        ->modalHeading(__('Delete block'))
                        ->modalSubmitActionLabel(__('Delete'))
                        ->modalCancelActionLabel(__('Cancel'))
                        ->requiresConfirmation())
                    ->extraItemActions([
                        Action::make('saveContentBlock')
                            ->label(__('Save block'))
                            ->tooltip(__('Save this block'))
                            ->icon(Heroicon::OutlinedDocumentCheck)
                            ->color('success')
                            ->extraAttributes(['class' => 'content-block-save-action'])
                            ->action(function (array $arguments, ?array $schemaState = null): void {
                                $this->saveBlock($arguments['item'] ?? null, $schemaState ?? []);
                            }),
                    ])
                    ->moveUpAction(fn (Action $action): Action => $action->label(__('Move up')))
                    ->moveDownAction(fn (Action $action): Action => $action->label(__('Move down')))
                    ->collapseAction(fn (Action $action): Action => $action
                        ->label(__('Collapse block'))
                        ->icon(Heroicon::ChevronUp))
                    ->expandAction(fn (Action $action): Action => $action
                        ->label(__('Expand block'))
                        ->icon(Heroicon::ChevronDown))
                    ->collapseAllAction(fn (Action $action): Action => $action->label(__('Collapse all')))
                    ->expandAllAction(fn (Action $action): Action => $action->label(__('Expand all')))
                    ->addActionAlignment(Alignment::Start)
                    ->blockPickerColumns(2)
                    ->blockPickerWidth('2xl')
                    ->columns(2)
                    ->columnSpanFull(),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $this->ensureAuthorized();

        $state = $this->form->getState();
        $record = $this->record();

        $update = [
            'structured_blocks' => $this->normalizeForStorage($state['blocks'] ?? []),
            'updated_by' => auth()->id(),
        ];

        if ($this->isContentOwner() && $this->usesLegacySource && blank($record->legacy_block_snapshot)) {
            $contentItem = $this->contentItem();
            $update['legacy_block_snapshot'] = app(LegacyContentBlockConverter::class)->snapshot($contentItem);
            $update['legacy_blocks_migrated_at'] = now();
        }

        $record->forceFill($update)->save();

        $this->usesLegacySource = false;
        $this->message = __('Content blocks saved.');
        $this->dispatch('content-block-saved', closeAll: true);
    }

    /**
     * @param  array<string, mixed>  $blockState
     */
    public function saveBlock(string | int | null $itemKey, array $blockState = []): void
    {
        $this->ensureAuthorized();

        if ($itemKey === null) {
            $this->message = __('Block could not be saved.');

            return;
        }

        $item = $this->builderItemForKey($itemKey);

        if (! is_array($item) || blank($item['type'] ?? null)) {
            $this->message = __('Block could not be saved.');

            return;
        }

        if ($blockState !== []) {
            $item['data'] = $blockState;
            $this->replaceBuilderItemState($itemKey, $blockState);
        }

        $normalizedBlock = $this->normalizeForStorage([$item])[0] ?? null;

        if (! is_array($normalizedBlock)) {
            $this->message = __('Block could not be saved.');

            return;
        }

        $record = $this->record();
        $update = [
            'structured_blocks' => $this->mergeSavedBlock(
                is_array($record->structured_blocks) ? $record->structured_blocks : [],
                $normalizedBlock,
                $this->currentBuilderUuidOrder(),
            ),
            'updated_by' => auth()->id(),
        ];

        if ($this->isContentOwner() && $this->usesLegacySource && blank($record->legacy_block_snapshot)) {
            $contentItem = $this->contentItem();
            $update['legacy_block_snapshot'] = app(LegacyContentBlockConverter::class)->snapshot($contentItem);
            $update['legacy_blocks_migrated_at'] = now();
        }

        $record->forceFill($update)->save();

        $this->message = __('Block saved.');
        $this->dispatch('content-block-saved', itemKey: (string) $itemKey, uuid: (string) $normalizedBlock['uuid']);
    }

    public function render(): View
    {
        return view('livewire.admin.content.content-block-editor');
    }

    private function ensureAuthorized(): void
    {
        abort_unless(auth()->user()?->can('access-admin'), 403);
    }

    private function contentItem(): ContentItem
    {
        return ContentItem::query()->findOrFail($this->contentItemId);
    }

    private function event(): Event
    {
        return Event::query()->findOrFail($this->eventId);
    }

    private function record(): ContentItem|Event
    {
        if ($this->isEventOwner()) {
            return $this->event();
        }

        return $this->contentItem();
    }

    private function isContentOwner(): bool
    {
        return $this->ownerType === 'content';
    }

    private function isEventOwner(): bool
    {
        return $this->ownerType === 'event';
    }

    private function localizeDirectAddAction(Action $action, string $label): Action
    {
        return $action
            ->label($label)
            ->icon(Heroicon::Plus)
            ->modalHidden()
            ->schema([])
            ->modalSubmitActionLabel(__('Add'))
            ->modalCancelActionLabel(__('Cancel'));
    }

    /**
     * @return array<int, BuilderBlock>
     */
    private function filamentBlocks(): array
    {
        return collect(app(PageBlockRegistry::class)->all())
            ->map(fn (PageBlock $block): BuilderBlock => BuilderBlock::make($block->key())
                ->label(fn (?array $state): string => $this->blockLabel($block, $state))
                ->icon($block->icon())
                ->schema([
                    ...$this->commonSchema($block),
                    ...$block->filamentSchema(),
                ])
                ->columns(2))
            ->values()
            ->all();
    }

    /**
     * @return array<int, mixed>
     */
    private function commonSchema(PageBlock $block): array
    {
        return [
            SchemaView::make('admin.content.page-block-previews.inline')
                ->viewData([
                    'blockLabel' => $block->label(),
                    'previewView' => $block->previewView(),
                ])
                ->columnSpanFull(),
            Hidden::make('uuid')
                ->default(fn (): string => (string) Str::uuid()),
            Select::make('layout')
                ->label(__('Block width'))
                ->options($this->layoutOptions())
                ->default('50')
                ->hiddenLabel()
                ->extraAttributes(['data-content-block-width-field' => 'true'])
                ->extraInputAttributes(['data-content-block-width-select' => 'true'])
                ->selectablePlaceholder(false)
                ->required()
                ->columnSpanFull(),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function layoutOptions(): array
    {
        return collect(range(15, 100, 5))
            ->mapWithKeys(fn (int $width): array => [(string) $width => $width.'%'])
            ->all();
    }

    private function blockLabel(PageBlock $block, ?array $state): string
    {
        if ($state === null) {
            return $block->label();
        }

        $candidate = data_get($state, 'data.title')
            ?: data_get($state, 'data.display_title')
            ?: data_get($state, 'data.label')
            ?: data_get($state, 'data.quote')
            ?: data_get($state, 'data.caption')
            ?: data_get($state, 'data.content')
            ?: $block->label();

        if (is_array($candidate)) {
            $candidate = $block->label();
        }

        return Str::limit(trim(strip_tags((string) $candidate)) ?: $block->label(), 70);
    }

    /**
     * @param  array<int, mixed>  $blocks
     * @return array<int, array{type: string, data: array<string, mixed>}>
     */
    private function toBuilderState(array $blocks): array
    {
        return collect($blocks)
            ->filter(fn (mixed $block): bool => is_array($block) && filled($block['type'] ?? null))
            ->map(fn (array $block): array => [
                'type' => (string) $block['type'],
                'data' => [
                    'uuid' => $block['uuid'] ?? (string) Str::uuid(),
                    'layout' => $this->normalizeLayout($block['layout'] ?? '50'),
                    'data' => $block['data'] ?? [],
                    'settings' => $block['settings'] ?? [],
                ],
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array<int, mixed>  $items
     * @return list<array{type: string, uuid: string, layout: string, data: array<string, mixed>, settings: array<string, mixed>}>
     */
    private function normalizeForStorage(array $items): array
    {
        $registry = app(PageBlockRegistry::class);

        return collect($items)
            ->filter(fn (mixed $item): bool => is_array($item) && filled($item['type'] ?? null))
            ->map(function (array $item) use ($registry): ?array {
                $block = $registry->get((string) $item['type']);

                if (! $block instanceof PageBlock) {
                    return null;
                }

                $raw = is_array($item['data'] ?? null) ? $item['data'] : [];
                $data = [
                    ...$block->defaultData(),
                    ...(is_array($raw['data'] ?? null) ? $raw['data'] : []),
                ];
                $settings = [
                    ...$block->defaultSettings(),
                    ...(is_array($raw['settings'] ?? null) ? $raw['settings'] : []),
                ];

                return [
                    'type' => $block->key(),
                    'uuid' => filled($raw['uuid'] ?? null) ? (string) $raw['uuid'] : (string) Str::uuid(),
                    'layout' => $this->normalizeLayout($raw['layout'] ?? '50'),
                    'data' => $this->normalizeData($block->key(), $data),
                    'settings' => $settings,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalizeData(string $type, array $data): array
    {
        if ($type === 'gallery' && isset($data['images']) && is_array($data['images'])) {
            $data['images'] = array_values($data['images']);
        }

        return $data;
    }

    private function builderItemForKey(string | int $itemKey): ?array
    {
        $blocks = $this->data['blocks'] ?? [];

        if (! is_array($blocks)) {
            return null;
        }

        if (array_key_exists($itemKey, $blocks) && is_array($blocks[$itemKey])) {
            return $blocks[$itemKey];
        }

        if (is_string($itemKey) && ctype_digit($itemKey) && array_key_exists((int) $itemKey, $blocks) && is_array($blocks[(int) $itemKey])) {
            return $blocks[(int) $itemKey];
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $blockState
     */
    private function replaceBuilderItemState(string | int $itemKey, array $blockState): void
    {
        if (array_key_exists($itemKey, $this->data['blocks'] ?? [])) {
            $this->data['blocks'][$itemKey]['data'] = $blockState;

            return;
        }

        if (is_string($itemKey) && ctype_digit($itemKey) && array_key_exists((int) $itemKey, $this->data['blocks'] ?? [])) {
            $this->data['blocks'][(int) $itemKey]['data'] = $blockState;
        }
    }

    /**
     * @return list<string>
     */
    private function currentBuilderUuidOrder(): array
    {
        return collect($this->data['blocks'] ?? [])
            ->map(fn (mixed $item): ?string => is_array($item) && filled(data_get($item, 'data.uuid'))
                ? (string) data_get($item, 'data.uuid')
                : null)
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @param  array<int, mixed>  $storedBlocks
     * @param  array{uuid: string} & array<string, mixed>  $savedBlock
     * @param  list<string>  $currentUuidOrder
     * @return list<array<string, mixed>>
     */
    private function mergeSavedBlock(array $storedBlocks, array $savedBlock, array $currentUuidOrder): array
    {
        $blocks = collect($storedBlocks)
            ->filter(fn (mixed $block): bool => is_array($block) && filled($block['type'] ?? null))
            ->values()
            ->all();
        $savedUuid = (string) ($savedBlock['uuid'] ?? '');

        foreach ($blocks as $index => $block) {
            if (filled($savedUuid) && (string) ($block['uuid'] ?? '') === $savedUuid) {
                $blocks[$index] = $savedBlock;

                return array_values($blocks);
            }
        }

        $insertIndex = $this->newBlockInsertIndex($blocks, $savedUuid, $currentUuidOrder);

        array_splice($blocks, $insertIndex, 0, [$savedBlock]);

        return array_values($blocks);
    }

    /**
     * @param  list<array<string, mixed>>  $storedBlocks
     * @param  list<string>  $currentUuidOrder
     */
    private function newBlockInsertIndex(array $storedBlocks, string $savedUuid, array $currentUuidOrder): int
    {
        $savedOrderIndex = array_search($savedUuid, $currentUuidOrder, true);

        if ($savedOrderIndex === false) {
            return count($storedBlocks);
        }

        for ($index = $savedOrderIndex - 1; $index >= 0; $index--) {
            $previousStoredIndex = $this->storedBlockIndexByUuid($storedBlocks, $currentUuidOrder[$index]);

            if ($previousStoredIndex !== null) {
                return $previousStoredIndex + 1;
            }
        }

        for ($index = $savedOrderIndex + 1, $count = count($currentUuidOrder); $index < $count; $index++) {
            $nextStoredIndex = $this->storedBlockIndexByUuid($storedBlocks, $currentUuidOrder[$index]);

            if ($nextStoredIndex !== null) {
                return $nextStoredIndex;
            }
        }

        return count($storedBlocks);
    }

    /**
     * @param  list<array<string, mixed>>  $storedBlocks
     */
    private function storedBlockIndexByUuid(array $storedBlocks, string $uuid): ?int
    {
        foreach ($storedBlocks as $index => $block) {
            if ((string) ($block['uuid'] ?? '') === $uuid) {
                return $index;
            }
        }

        return null;
    }

    private function normalizeLayout(mixed $layout): string
    {
        if (is_int($layout) || is_float($layout) || (is_string($layout) && preg_match('/^\d+$/', trim($layout)) === 1)) {
            $width = (int) round(((float) $layout) / 5) * 5;
            $width = max(15, min(100, $width));

            return (string) $width;
        }

        return match ((string) $layout) {
            'full' => '100',
            'half' => '50',
            'one-third' => '35',
            'two-thirds' => '65',
            'one-quarter' => '25',
            default => '50',
        };
    }
}
