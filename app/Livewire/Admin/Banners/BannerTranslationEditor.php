<?php

namespace App\Livewire\Admin\Banners;

use App\Models\Cms\Banner;
use App\Models\Cms\BannerTranslation;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Livewire\Component;

class BannerTranslationEditor extends Component
{
    public int $bannerId;

    /**
     * @var array<string, string>
     */
    public array $locales = [];

    /**
     * @var array<string, array<string, mixed>>
     */
    public array $translations = [];

    public string $selectedLocale = 'nl';

    public ?string $message = null;

    public string $messageLevel = 'success';

    /**
     * @param  array<string, string>  $locales
     */
    public function mount(Banner $banner, array $locales): void
    {
        $this->ensureAuthorized();

        $this->bannerId = $banner->id;
        $this->locales = $locales;
        $this->selectedLocale = array_key_first($locales) ?: 'nl';
        $this->loadTranslations();
    }

    public function selectLocale(string $locale): void
    {
        if (! array_key_exists($locale, $this->locales)) {
            return;
        }

        $this->selectedLocale = $locale;
    }

    public function save(): void
    {
        $this->ensureAuthorized();

        $localeKeys = array_keys($this->locales);
        $data = Validator::make(['translations' => $this->translations], [
            'translations' => ['array'],
            'translations.*.locale' => ['required', 'string', Rule::in($localeKeys)],
            'translations.*.title' => ['nullable', 'string', 'max:255'],
            'translations.*.subtitle' => ['nullable', 'string', 'max:255'],
            'translations.*.link_url' => ['nullable', 'string', 'max:255'],
            'translations.*.button_text' => ['nullable', 'string', 'max:255'],
            'translations.*.content' => ['nullable', 'string'],
            'translations.*.alt_text' => ['nullable', 'string', 'max:255'],
            'translations.*.aria_label' => ['nullable', 'string', 'max:255'],
            'translations.*.link_target' => ['nullable', Rule::in(['_self', '_blank'])],
        ])->validate();

        DB::transaction(function () use ($data): void {
            $banner = $this->banner();

            foreach ($data['translations'] as $locale => $row) {
                if ($this->isBlankTranslation($row)) {
                    $banner->translations()->where('locale', $locale)->delete();

                    continue;
                }

                $translation = $banner->translations()->firstOrNew(['locale' => $locale]);

                if (! $translation->exists) {
                    $translation->created_by = auth()->id();
                }

                $translation->fill([
                    'title' => $row['title'] ?? null,
                    'subtitle' => $row['subtitle'] ?? null,
                    'link_url' => $row['link_url'] ?? null,
                    'button_text' => $row['button_text'] ?? null,
                    'content' => $row['content'] ?? null,
                    'metadata' => [
                        'alt_text' => $row['alt_text'] ?? null,
                        'aria_label' => $row['aria_label'] ?? null,
                        'link_target' => $row['link_target'] ?? '_self',
                    ],
                    'updated_by' => auth()->id(),
                ])->save();
            }

            $primaryTranslation = $banner->translations()->where('locale', app()->getLocale())->first()
                ?: $banner->translations()->first();

            if ($primaryTranslation instanceof BannerTranslation) {
                $banner->fill([
                    'title' => $primaryTranslation->title ?: $banner->title,
                    'link_url' => $primaryTranslation->link_url ?: $banner->link_url,
                    'button_text' => $primaryTranslation->button_text ?: $banner->button_text,
                    'text' => $primaryTranslation->content ?: $banner->text,
                    'updated_by' => auth()->id(),
                ])->save();
            }
        });

        $this->loadTranslations();
        $this->messageLevel = 'success';
        $this->message = __('Banner translations saved.');
    }

    public function render(): View
    {
        return view('livewire.admin.banners.banner-translation-editor', [
            'selectedTranslation' => $this->translations[$this->selectedLocale] ?? $this->blankTranslation($this->selectedLocale),
        ]);
    }

    private function ensureAuthorized(): void
    {
        abort_unless(auth()->user()?->can('access-admin'), 403);
    }

    private function banner(): Banner
    {
        return Banner::query()
            ->with('translations')
            ->findOrFail($this->bannerId);
    }

    private function loadTranslations(): void
    {
        $banner = $this->banner();
        $translations = $banner->translations->keyBy('locale');

        $this->translations = collect($this->locales)
            ->mapWithKeys(function (string $label, string $locale) use ($translations): array {
                $translation = $translations->get($locale);

                return [$locale => $translation instanceof BannerTranslation
                    ? $this->translationToArray($translation, $label)
                    : $this->blankTranslation($locale, $label)];
            })
            ->all();

        if (! array_key_exists($this->selectedLocale, $this->translations)) {
            $this->selectedLocale = array_key_first($this->translations) ?: 'nl';
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function translationToArray(BannerTranslation $translation, string $label): array
    {
        $metadata = $translation->metadata ?? [];

        return [
            'locale' => $translation->locale,
            'label' => $label,
            'title' => $translation->title,
            'subtitle' => $translation->subtitle,
            'link_url' => $translation->link_url,
            'button_text' => $translation->button_text,
            'content' => $translation->content,
            'alt_text' => $metadata['alt_text'] ?? null,
            'aria_label' => $metadata['aria_label'] ?? null,
            'link_target' => $metadata['link_target'] ?? '_self',
            'exists' => true,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function blankTranslation(string $locale, ?string $label = null): array
    {
        return [
            'locale' => $locale,
            'label' => $label ?? ($this->locales[$locale] ?? strtoupper($locale)),
            'title' => null,
            'subtitle' => null,
            'link_url' => null,
            'button_text' => null,
            'content' => null,
            'alt_text' => null,
            'aria_label' => null,
            'link_target' => '_self',
            'exists' => false,
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function isBlankTranslation(array $row): bool
    {
        return blank($row['title'] ?? null)
            && blank($row['subtitle'] ?? null)
            && blank($row['link_url'] ?? null)
            && blank($row['button_text'] ?? null)
            && blank($row['content'] ?? null)
            && blank($row['alt_text'] ?? null)
            && blank($row['aria_label'] ?? null);
    }
}
