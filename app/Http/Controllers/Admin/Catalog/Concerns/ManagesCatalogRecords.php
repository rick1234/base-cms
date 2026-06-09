<?php

namespace App\Http\Controllers\Admin\Catalog\Concerns;

use App\Actions\Admin\Catalog\UpsertCatalogRecord;
use App\Http\Controllers\Admin\Concerns\UsesEditViewForCreate;
use App\Http\Requests\Admin\Catalog\CatalogRecordRequest;
use App\Models\Cms\CmsModel;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

trait ManagesCatalogRecords
{
    use UsesEditViewForCreate;

    public function index(Request $request): View
    {
        $modelClass = $this->modelClass();
        $query = $modelClass::query()->withCount('products');

        if ($request->filled('id')) {
            $query->whereKey($request->integer('id'));
        }

        if ($request->filled('name') || $request->filled('naam') || $request->filled('titel')) {
            $query->where('name', 'like', '%'.$request->input('name', $request->input('naam', $request->input('titel'))).'%');
        }

        if ($request->filled('status')) {
            $query->where('status', $this->normalizeRecordStatus($request->input('status')));
        }

        $records = $query
            ->orderBy($this->sortColumn($request->string('sort')->toString()), $request->string('sorttype')->lower()->toString() === 'asc' ? 'asc' : 'desc')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view('admin.catalog.records.index', [
            'records' => $records,
            'routeNames' => $this->recordRouteNames(),
            'title' => $this->titlePlural(),
            'section' => $this->sectionTitle(),
            'legacyClass' => $this->legacyOverviewClass(),
        ]);
    }

    public function store(CatalogRecordRequest $request, UpsertCatalogRecord $upsert): RedirectResponse
    {
        $record = $upsert->handle($this->modelClass(), $request->validated(), $request->user());

        flash(__(':record created.', ['record' => $this->titleSingular()]))->success();

        return redirect()->route($this->routeName('edit'), ['id' => $record->id]);
    }

    public function edit(Request $request): View
    {
        $record = $this->recordFromRequest($request) ?? new ($this->modelClass())([
            'status' => 'active',
        ]);

        return view('admin.catalog.records.edit', [
            'record' => $record,
            'routeNames' => $this->recordRouteNames(),
            'title' => $record->exists ? __('Edit :record', ['record' => $record->name]) : __('Toevoegen'),
            'section' => __('Edit :record', ['record' => $this->titleSingular()]),
            'backUrl' => route($this->routeName('index')),
            'deleteAction' => $record->exists ? route($this->routeName('destroy'), $record->id) : null,
            'extraFields' => $this->extraFields(),
        ]);
    }

    public function save(CatalogRecordRequest $request, UpsertCatalogRecord $upsert): RedirectResponse
    {
        $record = $upsert->handle($this->modelClass(), $request->validated(), $request->user(), $this->recordFromRequest($request));

        flash(__(':record saved.', ['record' => $this->titleSingular()]))->success();

        return redirect()->route($this->routeName('edit'), ['id' => $record->id]);
    }

    public function destroy(int $record): RedirectResponse
    {
        $modelClass = $this->modelClass();
        $modelClass::query()->findOrFail($record)->delete();

        flash(__(':record deleted.', ['record' => $this->titleSingular()]))->success();

        return redirect()->route($this->routeName('index'));
    }

    abstract protected function modelClass(): string;

    abstract protected function routePrefix(): string;

    abstract protected function titlePlural(): string;

    abstract protected function titleSingular(): string;

    abstract protected function legacyOverviewClass(): string;

    protected function sectionTitle(): string
    {
        return __('Overview');
    }

    /**
     * @return list<array{name: string, label: string, type: string}>
     */
    protected function extraFields(): array
    {
        return [];
    }

    private function recordFromRequest(Request $request): ?CmsModel
    {
        $id = (int) ($request->route('id') ?: $request->integer('id'));
        $modelClass = $this->modelClass();

        return $id > 0 ? $modelClass::query()->findOrFail($id) : null;
    }

    private function normalizeRecordStatus(mixed $status): string
    {
        return match ((string) $status) {
            '1', 'active' => 'active',
            '0', '2', '3', 'inactive' => 'inactive',
            default => (string) $status,
        };
    }

    private function sortColumn(string $sort): string
    {
        return match ($sort) {
            'naam', 'name', 'titel' => 'name',
            'status' => 'status',
            'sort_order', 'sort_index' => 'sort_order',
            default => 'id',
        };
    }

    /**
     * @return array<string, string>
     */
    private function recordRouteNames(): array
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
        return (request()->routeIs('cms.*') ? 'cms.catalog.' : 'admin.catalog.').$this->routePrefix().'.'.$name;
    }
}
