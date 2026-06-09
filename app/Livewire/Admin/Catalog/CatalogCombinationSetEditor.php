<?php

namespace App\Livewire\Admin\Catalog;

use App\Models\Cms\CatalogCombinationSet;
use App\Models\Cms\CatalogProduct;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;

class CatalogCombinationSetEditor extends Component
{
    public ?int $setId = null;

    public string $name = '';

    public string $description = '';

    public string $status = 'active';

    /**
     * @var list<int>
     */
    public array $productIds = [];

    /**
     * @var list<array{id: int, name: string, sku: string|null}>
     */
    public array $products = [];

    public ?string $message = null;

    public string $messageLevel = 'success';

    public function mount(?int $setId = null): void
    {
        $this->ensureAuthorized();

        $this->setId = $setId;
        $this->loadProducts();
        $this->loadSet();
    }

    public function toggleProduct(int $productId): void
    {
        if (in_array($productId, $this->productIds, true)) {
            $this->productIds = array_values(array_filter(
                $this->productIds,
                fn (int $selectedId): bool => $selectedId !== $productId,
            ));

            return;
        }

        $this->productIds[] = $productId;
    }

    public function save(): void
    {
        $this->ensureAuthorized();

        $data = Validator::make([
            'name' => $this->name,
            'description' => $this->description,
            'status' => $this->status,
            'product_ids' => $this->productIds,
        ], [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'product_ids' => ['array', 'min:1'],
            'product_ids.*' => ['integer', 'exists:catalog_products,id'],
        ])->validate();

        DB::transaction(function () use ($data): void {
            $set = $this->setId
                ? CatalogCombinationSet::query()->findOrFail($this->setId)
                : new CatalogCombinationSet(['created_by' => auth()->id()]);

            $set->fill([
                'name' => $data['name'],
                'slug' => Str::slug($data['name']),
                'description' => $data['description'] ?: null,
                'status' => $data['status'],
                'updated_by' => auth()->id(),
            ])->save();

            $set->products()->sync(
                collect($data['product_ids'])
                    ->unique()
                    ->values()
                    ->mapWithKeys(fn (int $productId, int $index): array => [$productId => ['sort_order' => $index + 1]])
                    ->all()
            );

            $this->setId = $set->id;
        });

        $this->loadSet();
        $this->messageLevel = 'success';
        $this->message = __('Combination set saved.');
    }

    public function render(): View
    {
        return view('livewire.admin.catalog.combination-set-editor');
    }

    private function ensureAuthorized(): void
    {
        abort_unless(auth()->user()?->can('access-admin'), 403);
    }

    private function loadProducts(): void
    {
        $this->products = CatalogProduct::query()
            ->orderBy('name')
            ->get(['id', 'name', 'sku'])
            ->map(fn (CatalogProduct $product): array => [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
            ])
            ->all();
    }

    private function loadSet(): void
    {
        if (! $this->setId) {
            return;
        }

        $set = CatalogCombinationSet::query()
            ->with('products')
            ->findOrFail($this->setId);

        $this->name = $set->name;
        $this->description = $set->description ?? '';
        $this->status = $set->status;
        $this->productIds = $set->products->pluck('id')->all();
    }
}
