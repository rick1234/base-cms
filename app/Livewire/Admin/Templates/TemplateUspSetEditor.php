<?php

namespace App\Livewire\Admin\Templates;

use App\Models\Cms\WebsiteTemplate;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Component;

class TemplateUspSetEditor extends Component
{
    public WebsiteTemplate $template;

    /**
     * @var list<array{name: string, location: string, items: list<array{label: string, icon: string}>}>
     */
    public array $sets = [];

    public ?string $message = null;

    public function mount(WebsiteTemplate $template): void
    {
        $this->template = $template;
        $this->sets = $this->normalizeSets($template->usp_sets ?? []);

        if ($this->sets === []) {
            $legacyItems = collect(data_get($template->default_settings ?? [], 'usp_items', []))
                ->map(fn (mixed $item): array => ['label' => trim((string) $item), 'icon' => 'done'])
                ->filter(fn (array $item): bool => $item['label'] !== '')
                ->values()
                ->all();

            $this->sets = [
                [
                    'name' => __('Default USP bar'),
                    'location' => 'header_top',
                    'items' => $legacyItems ?: [
                        ['label' => __('Responsive maatwerk'), 'icon' => 'done'],
                        ['label' => __('SEO vriendelijke basis'), 'icon' => 'done'],
                        ['label' => __('Veilig en snel beheer'), 'icon' => 'done'],
                    ],
                ],
            ];
        }
    }

    public function addSet(): void
    {
        $this->sets[] = [
            'name' => '',
            'location' => 'header_top',
            'items' => [
                ['label' => '', 'icon' => 'done'],
            ],
        ];
    }

    public function removeSet(int $setIndex): void
    {
        unset($this->sets[$setIndex]);
        $this->sets = array_values($this->sets);
    }

    public function moveSet(int $setIndex, string $direction): void
    {
        $targetIndex = $direction === 'up' ? $setIndex - 1 : $setIndex + 1;

        if (! isset($this->sets[$setIndex], $this->sets[$targetIndex])) {
            return;
        }

        [$this->sets[$setIndex], $this->sets[$targetIndex]] = [$this->sets[$targetIndex], $this->sets[$setIndex]];
    }

    public function addItem(int $setIndex): void
    {
        if (! isset($this->sets[$setIndex])) {
            return;
        }

        $this->sets[$setIndex]['items'][] = ['label' => '', 'icon' => 'done'];
    }

    public function removeItem(int $setIndex, int $itemIndex): void
    {
        if (! isset($this->sets[$setIndex]['items'][$itemIndex])) {
            return;
        }

        unset($this->sets[$setIndex]['items'][$itemIndex]);
        $this->sets[$setIndex]['items'] = array_values($this->sets[$setIndex]['items']);
    }

    public function moveItem(int $setIndex, int $itemIndex, string $direction): void
    {
        $targetIndex = $direction === 'up' ? $itemIndex - 1 : $itemIndex + 1;

        if (! isset($this->sets[$setIndex]['items'][$itemIndex], $this->sets[$setIndex]['items'][$targetIndex])) {
            return;
        }

        [$this->sets[$setIndex]['items'][$itemIndex], $this->sets[$setIndex]['items'][$targetIndex]] = [
            $this->sets[$setIndex]['items'][$targetIndex],
            $this->sets[$setIndex]['items'][$itemIndex],
        ];
    }

    public function save(): void
    {
        Gate::authorize('access-admin');

        $validated = $this->validate([
            'sets' => ['array'],
            'sets.*.name' => ['nullable', 'string', 'max:255'],
            'sets.*.location' => ['nullable', Rule::in(array_keys(config('cms_domains.usp_template_locations')))],
            'sets.*.items' => ['array'],
            'sets.*.items.*.label' => ['nullable', 'string', 'max:255'],
            'sets.*.items.*.icon' => ['nullable', 'string', 'max:80', 'regex:/^[a-z0-9_]+$/'],
        ]);

        $sets = $this->normalizeSets($validated['sets'] ?? []);

        $this->template->forceFill([
            'usp_sets' => $sets,
            'updated_by' => auth()->id(),
        ])->save();

        $this->template->refresh();
        $this->sets = $sets;
        $this->message = __('USP sets saved.');
    }

    public function render(): View
    {
        return view('livewire.admin.templates.template-usp-set-editor', [
            'locations' => config('cms_domains.usp_template_locations'),
        ]);
    }

    /**
     * @param  array<int, mixed>  $sets
     * @return list<array{name: string, location: string, items: list<array{label: string, icon: string}>}>
     */
    private function normalizeSets(array $sets): array
    {
        $locations = array_keys(config('cms_domains.usp_template_locations'));

        return collect($sets)
            ->map(function (mixed $set) use ($locations): array {
                $set = is_array($set) ? $set : [];
                $items = collect($set['items'] ?? [])
                    ->map(fn (mixed $item): array => [
                        'label' => trim((string) data_get($item, 'label', '')),
                        'icon' => trim((string) data_get($item, 'icon', 'done')) ?: 'done',
                    ])
                    ->filter(fn (array $item): bool => $item['label'] !== '')
                    ->values()
                    ->all();

                return [
                    'name' => trim((string) ($set['name'] ?? '')),
                    'location' => in_array(($set['location'] ?? null), $locations, true) ? (string) $set['location'] : 'header_top',
                    'items' => $items,
                ];
            })
            ->filter(fn (array $set): bool => $set['name'] !== '' || $set['items'] !== [])
            ->map(fn (array $set): array => [
                'name' => $set['name'] ?: __('USP set'),
                'location' => $set['location'],
                'items' => $set['items'],
            ])
            ->values()
            ->all();
    }
}
