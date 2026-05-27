<?php

namespace App\Livewire\Admin\Translations;

use App\Actions\Admin\Translations\SyncTranslationKeys;
use App\Models\Cms\TranslationKey;
use App\Support\Localization\TranslationRepository;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class TranslationManager extends Component
{
    use WithPagination;

    public string $area = '';

    public string $group = '';

    public string $status = '';

    public string $search = '';

    public string $locale = '';

    public bool $missing = false;

    public int $perPage = 40;

    /** @var array<int, string|null> */
    public array $values = [];

    /** @var array<int, string> */
    public array $valueStates = [];

    /** @var list<array{code: string, label: string}> */
    public array $languages = [];

    public ?int $editingId = null;

    public bool $editorOpen = false;

    public bool $usesCmsRoutes = false;

    public string $editorTitle = 'Toevoegen';

    /** @var array<string, mixed> */
    public array $form = [];

    public ?string $message = null;

    public string $messageLevel = 'success';

    /**
     * @var array<string, mixed>
     */
    protected array $queryString = [
        'area' => ['except' => ''],
        'group' => ['except' => ''],
        'status' => ['except' => ''],
        'search' => ['as' => 'q', 'except' => ''],
        'locale' => ['except' => ''],
        'missing' => ['except' => false],
    ];

    public function mount(?string $initialLocale = null, ?int $initialEditingId = null, bool $initialCreate = false, ?string $initialEditorTitle = null): void
    {
        $translations = app(TranslationRepository::class);

        $this->usesCmsRoutes = request()->routeIs('cms.*');
        $this->languages = $this->languageOptions($translations);
        $this->locale = $this->enabledLocale($initialLocale ?: request()->string('locale')->toString() ?: $translations->fallbackLocale());

        if ($initialEditingId) {
            $this->editKey($initialEditingId);
        } elseif ($initialCreate) {
            $this->newKey($initialEditorTitle);
        }
    }

    public function updated(string $property, mixed $value): void
    {
        if (str_starts_with($property, 'values.')) {
            $this->saveInlineValue((int) str($property)->after('values.')->before('.')->toString(), $value);

            return;
        }

        if (str_starts_with($property, 'form.values.')) {
            $locale = str($property)->after('form.values.')->before('.')->toString();
            $this->saveEditorValue($locale);

            return;
        }

        if (in_array($property, ['form.source_text', 'form.source_locale', 'form.description', 'form.status', 'form.is_system'], true)) {
            $this->autosaveDefinition();

            return;
        }

        if (in_array($property, ['area', 'group', 'status', 'search', 'missing', 'locale'], true)) {
            $this->locale = $this->enabledLocale($this->locale);
            $this->resetPage();
            $this->values = [];
            $this->valueStates = [];
        }
    }

    public function syncTranslations(SyncTranslationKeys $syncTranslationKeys): void
    {
        $this->authorizeAccess();

        $result = $syncTranslationKeys->handle();
        $this->values = [];
        $this->valueStates = [];
        $this->setMessage(__('Translations synchronized: :created created, :updated updated.', [
            'created' => $result['created'],
            'updated' => $result['updated'],
        ]));
    }

    public function newKey(?string $editorTitle = null): void
    {
        $this->editingId = null;
        $this->editorOpen = true;
        $this->editorTitle = $editorTitle ?: 'Toevoegen';
        $this->resetValidation();
        $this->form = $this->blankForm();
    }

    public function editKey(int $translationKeyId): void
    {
        $translationKey = TranslationKey::query()
            ->with('values')
            ->findOrFail($translationKeyId);

        $this->editingId = $translationKey->id;
        $this->editorOpen = true;
        $this->editorTitle = 'Edit translation';
        $this->resetValidation();
        $this->form = [
            'area' => $translationKey->area ?: 'shared',
            'group' => $translationKey->group ?: '*',
            'key' => $translationKey->key,
            'source_locale' => $translationKey->source_locale ?: app(TranslationRepository::class)->sourceLocale(),
            'source_text' => $translationKey->source_text,
            'description' => $translationKey->description,
            'status' => $translationKey->status ?: 'active',
            'is_system' => (bool) $translationKey->is_system,
            'values' => $this->valueForm($translationKey),
        ];
    }

    public function closeEditor(): void
    {
        $this->editorOpen = false;
        $this->editingId = null;
        $this->form = [];
        $this->resetValidation();
    }

    public function saveKey(): void
    {
        $this->authorizeAccess();

        $data = $this->validatedForm();
        $translationKey = $this->editingId
            ? TranslationKey::query()->findOrFail($this->editingId)
            : new TranslationKey;

        $translationKey->fill([
            'area' => $data['area'],
            'group' => $data['group'],
            'key' => $data['key'],
            'source_locale' => $data['source_locale'],
            'source_text' => $data['source_text'] ?: $data['key'],
            'description' => $data['description'],
            'status' => $data['status'],
            'is_system' => $data['is_system'],
            'updated_by' => auth()->id(),
        ]);

        if (! $translationKey->exists) {
            $translationKey->created_by = auth()->id();
        }

        $translationKey->save();
        $this->editingId = $translationKey->id;

        foreach ($data['values'] as $locale => $valueData) {
            $this->upsertValue($translationKey, $locale, $valueData['value'], (bool) $valueData['is_reviewed']);
        }

        $this->editKey($translationKey->id);
        $this->values[$translationKey->id] = $translationKey->valueFor($this->locale)?->value;
        $this->valueStates[$translationKey->id] = 'saved';
        $this->setMessage(__('Translation key saved.'));
    }

    public function deleteKey(int $translationKeyId): void
    {
        $this->authorizeAccess();

        $translationKey = TranslationKey::query()->findOrFail($translationKeyId);
        $translationKey->values()->delete();
        $translationKey->delete();

        unset($this->values[$translationKeyId], $this->valueStates[$translationKeyId]);

        if ($this->editingId === $translationKeyId) {
            $this->closeEditor();
        }

        $this->setMessage(__('Translation key deleted.'));
    }

    public function render(): View
    {
        $translationKeys = $this->translationKeys();
        $this->syncVisibleValues($translationKeys->getCollection());

        return view('livewire.admin.translations.translation-manager', [
            'translationKeys' => $translationKeys,
            'areas' => $this->areas(),
            'groups' => $this->groups(),
            'selectedLanguage' => $this->selectedLanguageLabel(),
        ]);
    }

    private function saveInlineValue(int $translationKeyId, mixed $value): void
    {
        $this->authorizeAccess();

        if ($translationKeyId <= 0) {
            return;
        }

        $value = is_string($value) ? $value : null;

        Validator::make(['value' => $value], [
            'value' => ['nullable', 'string'],
        ])->validate();

        $translationKey = TranslationKey::query()->find($translationKeyId);

        if (! $translationKey) {
            return;
        }

        $this->upsertValue($translationKey, $this->locale, $value, filled($value));
        $this->valueStates[$translationKeyId] = 'saved';
        $this->setMessage(__('Translation autosaved.'));
    }

    private function saveEditorValue(string $locale): void
    {
        if (! $this->editingId || ! $this->isEnabledLocale($locale)) {
            return;
        }

        $this->authorizeAccess();

        $translationKey = TranslationKey::query()->findOrFail($this->editingId);
        $valueData = $this->form['values'][$locale] ?? ['value' => null, 'is_reviewed' => false];

        Validator::make($valueData, [
            'value' => ['nullable', 'string'],
            'is_reviewed' => ['boolean'],
        ])->validate();

        $this->upsertValue($translationKey, $locale, $valueData['value'] ?? null, (bool) ($valueData['is_reviewed'] ?? false));

        if ($locale === $this->locale) {
            $this->values[$translationKey->id] = $valueData['value'] ?? null;
            $this->valueStates[$translationKey->id] = 'saved';
        }

        $this->setMessage(__('Translation autosaved.'));
    }

    private function autosaveDefinition(): void
    {
        if (! $this->editingId) {
            return;
        }

        $this->authorizeAccess();

        $translationKey = TranslationKey::query()->findOrFail($this->editingId);
        $data = Validator::make($this->form, [
            'source_locale' => ['required', 'string', 'max:16'],
            'source_text' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'is_system' => ['boolean'],
        ])->validate();

        $translationKey->fill([
            'source_locale' => app(TranslationRepository::class)->normalizeLocale($data['source_locale']),
            'source_text' => $data['source_text'],
            'description' => $data['description'],
            'status' => $data['status'],
            'is_system' => (bool) $data['is_system'],
            'updated_by' => auth()->id(),
        ])->save();

        $this->setMessage(__('Translation autosaved.'));
    }

    private function upsertValue(TranslationKey $translationKey, string $locale, ?string $value, bool $isReviewed): void
    {
        $translationKey->values()->updateOrCreate(
            ['locale' => app(TranslationRepository::class)->normalizeLocale($locale)],
            [
                'value' => $value,
                'status' => 'active',
                'is_reviewed' => $isReviewed,
                'reviewed_at' => $isReviewed ? now() : null,
                'updated_by' => auth()->id(),
            ],
        );

        $translationKey->unsetRelation('values');
        $translationKey->load('values');
    }

    private function translationKeys(): LengthAwarePaginator
    {
        $languages = collect($this->languages)->pluck('code')->all();

        return TranslationKey::query()
            ->with(['values' => fn ($query) => $query->whereIn('locale', $languages)])
            ->when($this->area !== '', fn (Builder $query) => $query->where('area', $this->area))
            ->when($this->group !== '', fn (Builder $query) => $query->where('group', $this->group))
            ->when($this->status !== '', fn (Builder $query) => $query->where('status', $this->status))
            ->when($this->missing, fn (Builder $query) => $query->whereDoesntHave('values', fn (Builder $valueQuery) => $valueQuery
                ->where('locale', $this->locale)
                ->whereNotNull('value')
                ->where('value', '<>', '')))
            ->when($this->search !== '', function (Builder $query): void {
                $query->where(function (Builder $query): void {
                    $query
                        ->where('key', 'like', '%'.$this->search.'%')
                        ->orWhere('source_text', 'like', '%'.$this->search.'%')
                        ->orWhereHas('values', fn (Builder $valueQuery) => $valueQuery->where('value', 'like', '%'.$this->search.'%'));
                });
            })
            ->orderBy('area')
            ->orderBy('group')
            ->orderBy('key')
            ->paginate($this->perPage);
    }

    /**
     * @param  Collection<int, TranslationKey>  $translationKeys
     */
    private function syncVisibleValues(Collection $translationKeys): void
    {
        $visibleIds = $translationKeys->pluck('id')->map(fn (int $id): string => (string) $id)->all();

        foreach ($translationKeys as $translationKey) {
            if (! array_key_exists($translationKey->id, $this->values)) {
                $this->values[$translationKey->id] = $translationKey->valueFor($this->locale)?->value;
            }
        }

        $this->values = collect($this->values)
            ->only($visibleIds)
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedForm(): array
    {
        $area = (string) ($this->form['area'] ?? 'shared');
        $group = (string) ($this->form['group'] ?? '*');

        $data = Validator::make($this->form, [
            'area' => ['required', Rule::in(array_keys($this->areas()))],
            'group' => ['required', 'string', 'max:120'],
            'key' => [
                'required',
                'string',
                'max:500',
                Rule::unique('translation_keys', 'key')
                    ->where('area', $area)
                    ->where('group', $group)
                    ->ignore($this->editingId),
            ],
            'source_locale' => ['required', 'string', 'max:16'],
            'source_text' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'is_system' => ['boolean'],
            'values' => ['array'],
            'values.*.value' => ['nullable', 'string'],
            'values.*.is_reviewed' => ['boolean'],
        ])->validate();

        $data['group'] = $data['group'] ?: '*';
        $data['source_locale'] = app(TranslationRepository::class)->normalizeLocale($data['source_locale']);
        $data['values'] = collect($this->languages)
            ->mapWithKeys(fn (array $language): array => [
                $language['code'] => [
                    'value' => $data['values'][$language['code']]['value'] ?? null,
                    'is_reviewed' => (bool) ($data['values'][$language['code']]['is_reviewed'] ?? false),
                ],
            ])
            ->all();

        return $data;
    }

    /**
     * @return array<string, string>
     */
    private function areas(): array
    {
        return [
            'admin' => __('Backend'),
            'frontend' => __('Frontend'),
            'shared' => __('Shared'),
        ];
    }

    /**
     * @return Collection<int, string>
     */
    private function groups(): Collection
    {
        return TranslationKey::query()
            ->select('group')
            ->distinct()
            ->orderBy('group')
            ->pluck('group')
            ->filter()
            ->values();
    }

    /**
     * @return array<string, mixed>
     */
    private function blankForm(): array
    {
        $sourceLocale = app(TranslationRepository::class)->sourceLocale();

        return [
            'area' => $this->area ?: 'shared',
            'group' => $this->group ?: '*',
            'key' => '',
            'source_locale' => $sourceLocale,
            'source_text' => '',
            'description' => '',
            'status' => 'active',
            'is_system' => false,
            'values' => collect($this->languages)
                ->mapWithKeys(fn (array $language): array => [
                    $language['code'] => [
                        'value' => '',
                        'is_reviewed' => false,
                    ],
                ])
                ->all(),
        ];
    }

    /**
     * @return array<string, array{value: ?string, is_reviewed: bool}>
     */
    private function valueForm(TranslationKey $translationKey): array
    {
        return collect($this->languages)
            ->mapWithKeys(fn (array $language): array => [
                $language['code'] => [
                    'value' => $translationKey->valueFor($language['code'])?->value,
                    'is_reviewed' => (bool) $translationKey->valueFor($language['code'])?->is_reviewed,
                ],
            ])
            ->all();
    }

    /**
     * @return list<array{code: string, label: string}>
     */
    private function languageOptions(TranslationRepository $translations): array
    {
        return $translations->enabledLanguages()
            ->map(fn ($language): array => [
                'code' => $translations->normalizeLocale($language->code),
                'label' => $language->label(),
            ])
            ->values()
            ->all();
    }

    private function selectedLanguageLabel(): string
    {
        return collect($this->languages)
            ->firstWhere('code', $this->locale)['label']
            ?? strtoupper($this->locale);
    }

    private function enabledLocale(string $locale): string
    {
        $translations = app(TranslationRepository::class);
        $locale = $translations->normalizeLocale($locale);

        if ($this->isEnabledLocale($locale)) {
            return $locale;
        }

        $firstLanguage = collect($this->languages)->first();

        return is_array($firstLanguage)
            ? $firstLanguage['code']
            : $translations->defaultLocale();
    }

    private function isEnabledLocale(string $locale): bool
    {
        return collect($this->languages)
            ->pluck('code')
            ->contains(app(TranslationRepository::class)->normalizeLocale($locale));
    }

    private function setMessage(string $message, string $level = 'success'): void
    {
        $this->message = $message;
        $this->messageLevel = $level;
    }

    private function authorizeAccess(): void
    {
        abort_unless(auth()->user()?->can('access-admin'), 403);
    }
}
