<?php

namespace App\Livewire\Admin\Catalog;

use App\Models\Cms\CatalogProduct;
use App\Models\Cms\CatalogProductVideo;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;

class CatalogProductVideoEditor extends Component
{
    public int $productId;

    /**
     * @var list<array<string, mixed>>
     */
    public array $videos = [];

    /**
     * @var array<string, string>
     */
    public array $providers = [
        'youtube' => 'YouTube',
        'vimeo' => 'Vimeo',
        'external' => 'Extern',
        'embed' => 'Embed',
    ];

    public ?string $message = null;

    public string $messageLevel = 'success';

    public function mount(int $productId): void
    {
        $this->ensureAuthorized();

        $this->productId = $productId;
        $this->loadVideos();
    }

    public function addVideo(): void
    {
        $this->videos[] = $this->blankVideo();
        $this->message = null;
    }

    public function duplicateVideo(int $index): void
    {
        if (! isset($this->videos[$index])) {
            return;
        }

        $video = $this->videos[$index];
        $video['key'] = $this->rowKey();
        $video['id'] = null;
        $video['title'] = filled($video['title'] ?? null)
            ? __('Copy of :name', ['name' => $video['title']])
            : __('Product video');

        $this->videos[] = $video;
        $this->message = null;
    }

    public function removeVideo(int $index): void
    {
        if (! isset($this->videos[$index])) {
            return;
        }

        array_splice($this->videos, $index, 1);
        $this->message = null;
    }

    public function moveVideoUp(int $index): void
    {
        if ($index <= 0 || ! isset($this->videos[$index], $this->videos[$index - 1])) {
            return;
        }

        [$this->videos[$index - 1], $this->videos[$index]] = [$this->videos[$index], $this->videos[$index - 1]];
    }

    public function moveVideoDown(int $index): void
    {
        if (! isset($this->videos[$index], $this->videos[$index + 1])) {
            return;
        }

        [$this->videos[$index + 1], $this->videos[$index]] = [$this->videos[$index], $this->videos[$index + 1]];
    }

    public function save(): void
    {
        $this->ensureAuthorized();

        $data = Validator::make(['videos' => $this->videos], [
            'videos' => ['array'],
            'videos.*.id' => ['nullable', 'integer', 'exists:catalog_product_videos,id'],
            'videos.*.title' => ['nullable', 'string', 'max:255'],
            'videos.*.url' => ['nullable', 'url', 'max:255'],
            'videos.*.provider' => ['nullable', 'string', 'max:255', Rule::in(array_keys($this->providers))],
        ])->validate();

        DB::transaction(function () use ($data): void {
            $product = $this->product();
            $seenIds = [];

            foreach (array_values($data['videos'] ?? []) as $index => $row) {
                if (blank($row['url'] ?? null)) {
                    continue;
                }

                $video = $this->existingVideo((int) ($row['id'] ?? 0))
                    ?? new CatalogProductVideo(['catalog_product_id' => $product->id, 'created_by' => auth()->id()]);

                $video->fill([
                    'title' => $row['title'] ?? null,
                    'url' => $row['url'],
                    'provider' => $row['provider'] ?? null,
                    'sort_order' => $index + 1,
                    'updated_by' => auth()->id(),
                ])->save();

                $seenIds[] = $video->id;
            }

            $product->videos()
                ->when($seenIds !== [], fn ($query) => $query->whereNotIn('id', $seenIds))
                ->delete();
        });

        $this->loadVideos();
        $this->messageLevel = 'success';
        $this->message = __('Product videos saved.');
    }

    public function render(): View
    {
        return view('livewire.admin.catalog.product-video-editor');
    }

    private function ensureAuthorized(): void
    {
        abort_unless(auth()->user()?->can('access-admin'), 403);
    }

    private function product(): CatalogProduct
    {
        return CatalogProduct::query()
            ->with('videos')
            ->findOrFail($this->productId);
    }

    private function loadVideos(): void
    {
        $this->videos = $this->product()
            ->videos
            ->map(fn (CatalogProductVideo $video): array => [
                'key' => $this->rowKey(),
                'id' => $video->id,
                'title' => $video->title,
                'url' => $video->url,
                'provider' => $this->providerValue($video->provider),
                'sort_order' => $video->sort_order,
            ])
            ->values()
            ->all();

        if ($this->videos === []) {
            $this->videos[] = $this->blankVideo();
        }
    }

    private function existingVideo(int $id): ?CatalogProductVideo
    {
        if ($id <= 0) {
            return null;
        }

        return $this->product()
            ->videos()
            ->whereKey($id)
            ->first();
    }

    /**
     * @return array<string, mixed>
     */
    private function blankVideo(): array
    {
        return [
            'key' => $this->rowKey(),
            'id' => null,
            'title' => '',
            'url' => '',
            'provider' => 'external',
            'sort_order' => count($this->videos) + 1,
        ];
    }

    private function providerValue(?string $provider): string
    {
        if ($provider && array_key_exists($provider, $this->providers)) {
            return $provider;
        }

        return 'external';
    }

    private function rowKey(): string
    {
        return 'catalog-video-'.Str::uuid()->toString();
    }
}
