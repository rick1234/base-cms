<?php

namespace App\Livewire\Admin\Catalog;

use App\Models\Cms\CatalogProduct;
use App\Models\Cms\CatalogProductTranslation;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;

class CatalogProductTranslationEditor extends Component
{
    public int $productId;

    /**
     * @var list<array<string, mixed>>
     */
    public array $translations = [];

    /**
     * @var list<string>
     */
    public array $locales = ['nl', 'en', 'de', 'fr'];

    public ?string $message = null;

    public string $messageLevel = 'success';

    public function mount(int $productId): void
    {
        $this->ensureAuthorized();

        $this->productId = $productId;
        $this->loadTranslations();
    }

    public function addTranslation(): void
    {
        $this->translations[] = $this->blankTranslation($this->nextUnusedLocale());
        $this->message = null;
    }

    public function duplicateTranslation(int $index): void
    {
        if (! isset($this->translations[$index])) {
            return;
        }

        $translation = $this->translations[$index];
        $translation['key'] = $this->rowKey();
        $translation['id'] = null;
        $translation['locale'] = $this->nextUnusedLocale();
        $translation['title'] = filled($translation['title'] ?? null)
            ? __('Copy of :name', ['name' => $translation['title']])
            : __('Product translation');

        $this->translations[] = $translation;
        $this->message = null;
    }

    public function removeTranslation(int $index): void
    {
        if (! isset($this->translations[$index])) {
            return;
        }

        array_splice($this->translations, $index, 1);
        $this->message = null;
    }

    public function moveTranslationUp(int $index): void
    {
        if ($index <= 0 || ! isset($this->translations[$index], $this->translations[$index - 1])) {
            return;
        }

        [$this->translations[$index - 1], $this->translations[$index]] = [$this->translations[$index], $this->translations[$index - 1]];
    }

    public function moveTranslationDown(int $index): void
    {
        if (! isset($this->translations[$index], $this->translations[$index + 1])) {
            return;
        }

        [$this->translations[$index + 1], $this->translations[$index]] = [$this->translations[$index], $this->translations[$index + 1]];
    }

    public function save(): void
    {
        $this->ensureAuthorized();

        $data = Validator::make(['translations' => $this->translations], [
            'translations' => ['array'],
            'translations.*.id' => ['nullable', 'integer', 'exists:catalog_product_translations,id'],
            'translations.*.locale' => ['required', 'string', 'max:8', Rule::in($this->locales)],
            'translations.*.title' => ['nullable', 'string', 'max:255'],
            'translations.*.subtitle' => ['nullable', 'string', 'max:255'],
            'translations.*.button_text' => ['nullable', 'string', 'max:255'],
            'translations.*.link_url' => ['nullable', 'string', 'max:255'],
            'translations.*.content' => ['nullable', 'string'],
        ])->validate();

        DB::transaction(function () use ($data): void {
            $product = $this->product();
            $seenIds = [];
            $seenLocales = [];

            foreach (array_values($data['translations'] ?? []) as $row) {
                $locale = (string) ($row['locale'] ?? '');

                if ($locale === '' || in_array($locale, $seenLocales, true) || $this->translationIsBlank($row)) {
                    continue;
                }

                $translation = $this->existingTranslation((int) ($row['id'] ?? 0), $locale)
                    ?? $product->translations()->firstOrNew(['locale' => $locale]);

                if (! $translation->exists) {
                    $translation->created_by = auth()->id();
                }

                $translation->fill([
                    'locale' => $locale,
                    'title' => $row['title'] ?? null,
                    'subtitle' => $row['subtitle'] ?? null,
                    'button_text' => $row['button_text'] ?? null,
                    'link_url' => $row['link_url'] ?? null,
                    'content' => $row['content'] ?? null,
                    'updated_by' => auth()->id(),
                ])->save();

                $seenIds[] = $translation->id;
                $seenLocales[] = $locale;
            }

            $product->translations()
                ->when($seenIds !== [], fn ($query) => $query->whereNotIn('id', $seenIds))
                ->delete();
        });

        $this->loadTranslations();
        $this->messageLevel = 'success';
        $this->message = __('Product translations saved.');
    }

    public function render(): View
    {
        return view('livewire.admin.catalog.product-translation-editor');
    }

    private function ensureAuthorized(): void
    {
        abort_unless(auth()->user()?->can('access-admin'), 403);
    }

    private function product(): CatalogProduct
    {
        return CatalogProduct::query()
            ->with('translations')
            ->findOrFail($this->productId);
    }

    private function loadTranslations(): void
    {
        $rows = $this->product()
            ->translations
            ->sortBy(fn (CatalogProductTranslation $translation): int => array_search($translation->locale, $this->locales, true) ?: 99)
            ->values()
            ->map(fn (CatalogProductTranslation $translation): array => [
                'key' => $this->rowKey(),
                'id' => $translation->id,
                'locale' => $translation->locale,
                'title' => $translation->title,
                'subtitle' => $translation->subtitle,
                'button_text' => $translation->button_text,
                'link_url' => $translation->link_url,
                'content' => $translation->content,
            ])
            ->all();

        $existingLocales = collect($rows)->pluck('locale')->all();

        foreach ($this->locales as $locale) {
            if (! in_array($locale, $existingLocales, true)) {
                $rows[] = $this->blankTranslation($locale);
            }
        }

        $this->translations = $rows;
    }

    private function existingTranslation(int $id, string $locale): ?CatalogProductTranslation
    {
        $query = $this->product()->translations();

        if ($id > 0) {
            return $query->whereKey($id)->first();
        }

        return $query->where('locale', $locale)->first();
    }

    /**
     * @param  array<string, mixed>  $translation
     */
    private function translationIsBlank(array $translation): bool
    {
        return blank($translation['title'] ?? null)
            && blank($translation['subtitle'] ?? null)
            && blank($translation['button_text'] ?? null)
            && blank($translation['link_url'] ?? null)
            && blank($translation['content'] ?? null);
    }

    /**
     * @return array<string, mixed>
     */
    private function blankTranslation(string $locale): array
    {
        return [
            'key' => $this->rowKey(),
            'id' => null,
            'locale' => $locale,
            'title' => '',
            'subtitle' => '',
            'button_text' => '',
            'link_url' => '',
            'content' => '',
        ];
    }

    private function nextUnusedLocale(): string
    {
        $used = collect($this->translations)->pluck('locale')->filter()->all();

        return collect($this->locales)->first(fn (string $locale): bool => ! in_array($locale, $used, true)) ?: app()->getLocale();
    }

    private function rowKey(): string
    {
        return 'catalog-translation-'.Str::uuid()->toString();
    }
}
