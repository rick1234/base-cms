<?php

namespace App\Livewire\Admin\Templates;

use App\Models\Cms\WebsiteTemplate;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;

class TemplateSectionEditor extends Component
{
    public WebsiteTemplate $template;

    /**
     * @var list<array{handle: string, label: string, type: string}>
     */
    public array $sections = [];

    public ?string $message = null;

    public function mount(WebsiteTemplate $template): void
    {
        $this->template = $template;
        $this->sections = collect($template->defined_sections ?? [])
            ->map(fn (array $section): array => [
                'handle' => (string) ($section['handle'] ?? ''),
                'label' => (string) ($section['label'] ?? ''),
                'type' => (string) ($section['type'] ?? 'banner'),
            ])
            ->values()
            ->all();

        if ($this->sections === []) {
            $this->sections = [
                ['handle' => 'homepage_hero', 'label' => __('Homepage hero'), 'type' => 'banner'],
                ['handle' => 'homepage_right_block', 'label' => __('Homepage Right Block'), 'type' => 'banner'],
            ];
        }
    }

    public function addSection(): void
    {
        $this->sections[] = ['handle' => '', 'label' => '', 'type' => 'banner'];
    }

    public function removeSection(int $index): void
    {
        unset($this->sections[$index]);
        $this->sections = array_values($this->sections);
    }

    public function moveSection(int $index, string $direction): void
    {
        $targetIndex = $direction === 'up' ? $index - 1 : $index + 1;

        if (! isset($this->sections[$index], $this->sections[$targetIndex])) {
            return;
        }

        [$this->sections[$index], $this->sections[$targetIndex]] = [$this->sections[$targetIndex], $this->sections[$index]];
    }

    public function updatedSections(mixed $value, string $key): void
    {
        if (! str_ends_with($key, '.label')) {
            return;
        }

        $index = (int) Str::before($key, '.');

        if (($this->sections[$index]['handle'] ?? '') !== '') {
            return;
        }

        $this->sections[$index]['handle'] = Str::of((string) $value)
            ->lower()
            ->ascii()
            ->replaceMatches('/[^a-z0-9]+/', '_')
            ->trim('_')
            ->toString();
    }

    public function save(): void
    {
        Gate::authorize('access-admin');

        $validated = $this->validate([
            'sections' => ['array'],
            'sections.*.handle' => ['nullable', 'string', 'max:120', 'regex:/^[a-z0-9]+(?:[_-][a-z0-9]+)*$/'],
            'sections.*.label' => ['nullable', 'string', 'max:255'],
            'sections.*.type' => ['nullable', Rule::in(['banner', 'mixed'])],
        ]);

        $sections = collect($validated['sections'] ?? [])
            ->map(fn (array $section): array => [
                'handle' => trim((string) ($section['handle'] ?? '')),
                'label' => trim((string) ($section['label'] ?? '')),
                'type' => in_array(($section['type'] ?? 'banner'), ['banner', 'mixed'], true) ? (string) ($section['type'] ?? 'banner') : 'banner',
            ])
            ->filter(fn (array $section): bool => $section['handle'] !== '' || $section['label'] !== '')
            ->map(fn (array $section): array => [
                'handle' => $section['handle'],
                'label' => $section['label'] ?: str($section['handle'])->replace(['_', '-'], ' ')->title()->toString(),
                'type' => $section['type'],
            ])
            ->values()
            ->all();

        $this->template->forceFill([
            'defined_sections' => $sections,
            'updated_by' => auth()->id(),
        ])->save();

        $this->template->refresh();
        $this->sections = $sections;
        $this->message = __('Template sections saved.');
    }

    public function render(): View
    {
        $previewTemplate = $this->template->replicate();
        $previewTemplate->forceFill([
            'id' => $this->template->id,
            'defined_sections' => $this->sections,
        ]);

        return view('livewire.admin.templates.template-section-editor', [
            'previewTemplate' => $previewTemplate,
        ]);
    }
}
