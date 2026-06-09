<?php

namespace App\Http\Controllers\Admin\Catalog;

use App\Http\Controllers\Controller;
use App\Models\Cms\CatalogCombinationSet;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CatalogCombinationSetController extends Controller
{
    public function index(Request $request): View
    {
        $query = CatalogCombinationSet::query()->withCount('products');

        if ($request->filled('id')) {
            $query->whereKey($request->integer('id'));
        }

        if ($request->filled('name')) {
            $query->where('name', 'like', '%'.$request->string('name')->toString().'%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        $sets = $query
            ->orderBy(match ($request->string('sort')->toString()) {
                'name' => 'name',
                'status' => 'status',
                default => 'id',
            }, $request->string('sorttype')->lower()->toString() === 'asc' ? 'asc' : 'desc')
            ->paginate(25)
            ->withQueryString();

        return view('admin.catalog.combination-sets.index', [
            'sets' => $sets,
            'routeNames' => $this->routeNames(),
        ]);
    }

    public function create(): View
    {
        return $this->edit(null);
    }

    public function edit(?int $id = null): View
    {
        $set = $id ? CatalogCombinationSet::query()->findOrFail($id) : null;

        return view('admin.catalog.combination-sets.edit', [
            'set' => $set,
            'routeNames' => $this->routeNames(),
            'backUrl' => route('admin.catalog.combination-sets.index'),
        ]);
    }

    public function destroy(int $id): RedirectResponse
    {
        CatalogCombinationSet::query()->findOrFail($id)->delete();

        flash(__('Combination set deleted.'))->success();

        return redirect()->route('admin.catalog.combination-sets.index');
    }

    /**
     * @return array<string, string>
     */
    private function routeNames(): array
    {
        return [
            'index' => 'admin.catalog.combination-sets.index',
            'create' => 'admin.catalog.combination-sets.create',
            'edit' => 'admin.catalog.combination-sets.edit',
            'destroy' => 'admin.catalog.combination-sets.destroy',
        ];
    }
}
