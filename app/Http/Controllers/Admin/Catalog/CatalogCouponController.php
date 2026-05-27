<?php

namespace App\Http\Controllers\Admin\Catalog;

use App\Http\Controllers\Admin\Concerns\UsesEditViewForCreate;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Catalog\CatalogCouponRequest;
use App\Models\Cms\CatalogCoupon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class CatalogCouponController extends Controller
{
    use UsesEditViewForCreate;

    public function index(Request $request): View
    {
        $query = CatalogCoupon::query();

        if ($request->filled('id')) {
            $query->whereKey($request->integer('id'));
        }

        if ($request->filled('name') || $request->filled('naam')) {
            $query->where('name', 'like', '%'.$request->input('name', $request->input('naam')).'%');
        }

        if ($request->filled('code')) {
            $query->where('code', 'like', '%'.$request->input('code').'%');
        }

        if ($request->filled('status')) {
            $query->where('is_active', in_array((string) $request->input('status'), ['1', 'active'], true));
        }

        $coupons = $query
            ->orderBy($this->sortColumn($request->string('sort')->toString()), $request->string('sorttype')->lower()->toString() === 'asc' ? 'asc' : 'desc')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view('admin.catalog.coupons.index', [
            'coupons' => $coupons,
            'routeNames' => $this->routeNames(),
        ]);
    }

    public function store(CatalogCouponRequest $request): RedirectResponse
    {
        $coupon = $this->persist($request);

        flash(__('Action code created.'))->success();

        return redirect()->route($this->routeName('edit'), ['id' => $coupon->id]);
    }

    public function edit(Request $request): View
    {
        $coupon = $this->couponFromRequest($request) ?? new CatalogCoupon([
            'percentage_discount' => 0,
            'minimum_amount' => 0,
            'is_active' => true,
            'usage_mode' => 'any',
        ]);

        return view('admin.catalog.coupons.edit', [
            'coupon' => $coupon,
            'routeNames' => $this->routeNames(),
            'pageName' => __('Edit action code'),
            'backUrl' => route($this->routeName('index')),
            'deleteAction' => $coupon->exists ? route($this->routeName('destroy'), $coupon) : null,
        ]);
    }

    public function save(CatalogCouponRequest $request): RedirectResponse
    {
        $coupon = $this->persist($request, $request->coupon());

        flash(__('Action code saved.'))->success();

        return redirect()->route($this->routeName('edit'), ['id' => $coupon->id]);
    }

    public function destroy(CatalogCoupon $catalogCoupon): RedirectResponse
    {
        $catalogCoupon->delete();

        flash(__('Action code deleted.'))->success();

        return redirect()->route($this->routeName('index'));
    }

    private function persist(CatalogCouponRequest $request, ?CatalogCoupon $coupon = null): CatalogCoupon
    {
        $coupon ??= new CatalogCoupon;
        $data = $request->validated();
        $attributes = Arr::only($data, [
            'name',
            'code',
            'percentage_discount',
            'starts_at',
            'ends_at',
            'is_active',
            'usage_mode',
        ]);
        $attributes['minimum_amount'] = $this->moneyToCents($data['minimum_amount'] ?? 0);

        if (! $coupon->exists) {
            $attributes['created_by'] = $request->user()?->id;
        }

        $attributes['updated_by'] = $request->user()?->id;

        $coupon->fill($attributes)->save();

        return $coupon;
    }

    private function couponFromRequest(Request $request): ?CatalogCoupon
    {
        $id = (int) ($request->route('id') ?: $request->integer('id'));

        return $id > 0 ? CatalogCoupon::query()->findOrFail($id) : null;
    }

    private function sortColumn(string $sort): string
    {
        return match ($sort) {
            'naam', 'name' => 'name',
            'code' => 'code',
            'korting', 'percentage_discount' => 'percentage_discount',
            'status', 'is_active' => 'is_active',
            default => 'id',
        };
    }

    private function moneyToCents(mixed $value): int
    {
        $normalized = str_replace(',', '.', (string) $value);
        $normalized = preg_replace('/[^0-9.-]/', '', $normalized) ?: '0';

        return (int) round(((float) $normalized) * 100);
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
        return (request()->routeIs('cms.*') ? 'cms.catalog.coupons.' : 'admin.catalog.coupons.').$name;
    }
}
