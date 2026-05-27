<?php

namespace App\Http\Controllers\Admin\Redirects;

use App\Actions\Admin\Redirects\UpsertRedirect;
use App\Http\Controllers\Admin\Concerns\UsesEditViewForCreate;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Redirects\RedirectInlineRequest;
use App\Http\Requests\Admin\Redirects\RedirectRequest;
use App\Models\Cms\CmsRedirect;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RedirectController extends Controller
{
    use UsesEditViewForCreate;

    public function index(Request $request): View
    {
        $query = CmsRedirect::query();

        if ($request->filled('id')) {
            $query->whereKey($request->integer('id'));
        }

        if ($request->filled('source_path') || $request->filled('old_link')) {
            $query->where('source_path', 'like', '%'.CmsRedirect::normalizeSourcePath($request->input('source_path', $request->input('old_link'))).'%');
        }

        if ($request->filled('target_url') || $request->filled('link')) {
            $query->where('target_url', 'like', '%'.$request->input('target_url', $request->input('link')).'%');
        }

        if ($request->filled('status_code')) {
            $query->where('status_code', $request->integer('status_code'));
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $redirects = $query
            ->orderBy($this->sortColumn($request->string('sort')->toString()), $request->string('sorttype')->lower()->toString() === 'desc' ? 'desc' : 'asc')
            ->orderBy('source_path')
            ->paginate(25)
            ->withQueryString();

        return view('admin.redirects.index', [
            'redirects' => $redirects,
            'statusCodes' => CmsRedirect::statusCodes(),
            'routeNames' => $this->routeNames(),
        ]);
    }

    public function store(RedirectRequest $request, UpsertRedirect $upsert): RedirectResponse
    {
        $redirect = $upsert->handle($request->validated(), $request->user());

        flash(__('Redirect created.'))->success();

        return redirect()->route($this->routeName('edit'), ['id' => $redirect->id]);
    }

    public function edit(Request $request): View
    {
        $redirect = $this->redirectFromRequest($request);

        return view('admin.redirects.edit', [
            'redirect' => $redirect ?? new CmsRedirect([
                'status_code' => 301,
                'is_active' => true,
                'preserve_query' => false,
            ]),
            'statusCodes' => CmsRedirect::statusCodes(),
            'routeNames' => $this->routeNames(),
            'pageName' => __('Edit redirect'),
            'backUrl' => route($this->routeName('index')),
            'deleteAction' => $redirect ? route($this->routeName('destroy'), $redirect) : null,
        ]);
    }

    public function save(RedirectRequest $request, UpsertRedirect $upsert): RedirectResponse
    {
        $redirect = $upsert->handle($request->validated(), $request->user(), $request->redirect());

        flash(__('Redirect saved.'))->success();

        return redirect()->route($this->routeName('edit'), ['id' => $redirect->id]);
    }

    public function update(CmsRedirect $redirect, RedirectRequest $request, UpsertRedirect $upsert): RedirectResponse
    {
        $redirect = $upsert->handle($request->validated(), $request->user(), $redirect);

        flash(__('Redirect saved.'))->success();

        return redirect()->route($this->routeName('edit'), ['id' => $redirect->id]);
    }

    public function updateSource(RedirectInlineRequest $request): JsonResponse|RedirectResponse
    {
        $redirect = CmsRedirect::query()->findOrFail($request->redirectId());
        $redirect->fill([
            'source_path' => $request->validated('newValue'),
            'updated_by' => $request->user()?->id,
        ])->save();

        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'success',
                'source_path' => $redirect->source_path,
            ]);
        }

        flash(__('Redirect source updated.'))->success();

        return back();
    }

    public function destroy(CmsRedirect $redirect): RedirectResponse
    {
        $redirect->delete();

        flash(__('Redirect deleted.'))->success();

        return redirect()->route($this->routeName('index'));
    }

    public function destroyAjax(Request $request): JsonResponse|RedirectResponse
    {
        $redirect = CmsRedirect::query()->findOrFail($request->integer('redirectid') ?: $request->integer('id'));
        $redirect->delete();

        if ($request->expectsJson()) {
            return response()->json(['status' => 'success']);
        }

        flash(__('Redirect deleted.'))->success();

        return redirect()->route($this->routeName('index'));
    }

    private function redirectFromRequest(Request $request): ?CmsRedirect
    {
        $id = (int) ($request->route('id') ?: $request->integer('id'));

        return $id > 0 ? CmsRedirect::query()->findOrFail($id) : null;
    }

    private function sortColumn(string $sort): string
    {
        return match ($sort) {
            'old_link', 'source', 'source_path' => 'source_path',
            'link', 'target', 'target_url' => 'target_url',
            'status', 'status_code' => 'status_code',
            'hits', 'hit_count' => 'hit_count',
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
            'update' => $this->routeName('update'),
            'destroy' => $this->routeName('destroy'),
            'source.update' => $this->routeName('source.update'),
            'ajax.destroy' => $this->routeName('ajax.destroy'),
        ];
    }

    private function routeName(string $name): string
    {
        return (request()->routeIs('cms.*') ? 'cms.redirects.' : 'admin.redirects.').$name;
    }
}
