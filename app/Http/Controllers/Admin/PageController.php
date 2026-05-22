<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Cms\UpsertPage;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PageRequest;
use App\Models\Cms\Page;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PageController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Page::class);

        return view('admin.pages.index', [
            'pages' => Page::query()->ordered()->paginate(20),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Page::class);

        return view('admin.pages.create', [
            'page' => new Page(['status' => 'draft', 'template' => 'default']),
        ]);
    }

    public function store(PageRequest $request, UpsertPage $upsertPage): RedirectResponse
    {
        $page = $upsertPage->handle($request->validated(), $request->user());

        return redirect()
            ->route('admin.pages.edit', $page)
            ->with('status', __('Page created.'));
    }

    public function edit(Page $page): View
    {
        $this->authorize('update', $page);

        return view('admin.pages.edit', [
            'page' => $page,
        ]);
    }

    public function update(PageRequest $request, Page $page, UpsertPage $upsertPage): RedirectResponse
    {
        $upsertPage->handle($request->validated(), $request->user(), $page);

        return redirect()
            ->route('admin.pages.edit', $page)
            ->with('status', __('Page updated.'));
    }
}
