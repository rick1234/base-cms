<?php

namespace App\Http\Controllers\Admin\Catalog;

use App\Http\Controllers\Admin\Concerns\UsesEditViewForCreate;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Catalog\CatalogReviewRequest;
use App\Models\Cms\CatalogProduct;
use App\Models\Cms\CatalogReview;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class CatalogReviewController extends Controller
{
    use UsesEditViewForCreate;

    public function index(Request $request): View
    {
        $query = CatalogReview::query()->with('product');

        if ($request->filled('id')) {
            $query->whereKey($request->integer('id'));
        }

        if ($request->filled('product') || $request->filled('artikel')) {
            $term = $request->input('product', $request->input('artikel'));
            $query->whereHas('product', fn ($productQuery) => $productQuery->where('name', 'like', '%'.$term.'%'));
        }

        if ($request->filled('status')) {
            $query->where('status', $this->normalizeStatus($request->input('status')));
        }

        $reviews = $query
            ->orderBy($this->sortColumn($request->string('sort')->toString()), $request->string('sorttype')->lower()->toString() === 'asc' ? 'asc' : 'desc')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view('admin.catalog.reviews.index', [
            'reviews' => $reviews,
            'routeNames' => $this->routeNames(),
        ]);
    }

    public function store(CatalogReviewRequest $request): RedirectResponse
    {
        $review = $this->persist($request);

        flash(__('Review created.'))->success();

        return redirect()->route($this->routeName('edit'), ['id' => $review->id]);
    }

    public function edit(Request $request): View
    {
        $review = $this->reviewFromRequest($request) ?? new CatalogReview([
            'catalog_product_id' => $request->integer('product_id') ?: $request->integer('catalog_product_id') ?: null,
            'status' => 'pending',
        ]);

        return view('admin.catalog.reviews.edit', [
            'review' => $review,
            'products' => CatalogProduct::query()->orderBy('name')->get(),
            'routeNames' => $this->routeNames(),
            'pageName' => __('Edit catalog review'),
            'backUrl' => route($this->routeName('index')),
            'deleteAction' => $review->exists ? route($this->routeName('destroy'), $review) : null,
        ]);
    }

    public function save(CatalogReviewRequest $request): RedirectResponse
    {
        $review = $this->persist($request, $request->review());

        flash(__('Review saved.'))->success();

        return redirect()->route($this->routeName('edit'), ['id' => $review->id]);
    }

    public function destroy(CatalogReview $catalogReview): RedirectResponse
    {
        $catalogReview->delete();

        flash(__('Review deleted.'))->success();

        return redirect()->route($this->routeName('index'));
    }

    private function persist(CatalogReviewRequest $request, ?CatalogReview $review = null): CatalogReview
    {
        $review ??= new CatalogReview;
        $attributes = Arr::only($request->validated(), [
            'catalog_product_id',
            'author_name',
            'author_email',
            'rating',
            'status',
            'title',
            'content',
        ]);

        if (! $review->exists) {
            $attributes['created_by'] = $request->user()?->id;
        }

        $attributes['updated_by'] = $request->user()?->id;

        $review->fill($attributes)->save();

        return $review;
    }

    private function reviewFromRequest(Request $request): ?CatalogReview
    {
        $id = (int) ($request->route('id') ?: $request->integer('id'));

        return $id > 0 ? CatalogReview::query()->findOrFail($id) : null;
    }

    private function normalizeStatus(mixed $status): string
    {
        return match ((string) $status) {
            '1', 'active', 'published' => 'published',
            '0', '2', 'inactive', 'rejected' => 'rejected',
            default => (string) $status,
        };
    }

    private function sortColumn(string $sort): string
    {
        return match ($sort) {
            'rating', 'score' => 'rating',
            'status' => 'status',
            default => 'id',
        };
    }

    /**
     * @return array<string, string>
     */
    private function routeNames(): array
    {
        return [
            'index' => $this->routeName('index'),
            'store' => $this->routeName('store'),
            'create' => request()->routeIs('cms.*') ? $this->routeName('edit') : $this->routeName('create'),
            'edit' => $this->routeName('edit'),
            'save' => $this->routeName('save'),
            'destroy' => $this->routeName('destroy'),
        ];
    }

    private function routeName(string $name): string
    {
        return (request()->routeIs('cms.*') ? 'cms.catalog.reviews.' : 'admin.catalog.reviews.').$name;
    }
}
