<?php

namespace App\Http\Controllers\Admin\Domains;

use App\Actions\Admin\Domains\UpsertWebsiteTemplate;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Domains\WebsiteTemplateRequest;
use App\Models\Cms\WebsiteTemplate;
use App\Support\Domains\TemplateScaffoldGenerator;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class WebsiteTemplateController extends Controller
{
    public function index(): View
    {
        return view('admin.templates.index', [
            'templates' => WebsiteTemplate::query()
                ->withCount('domains')
                ->ordered()
                ->paginate(25),
        ]);
    }

    public function create(): View
    {
        return $this->form(new WebsiteTemplate);
    }

    public function store(WebsiteTemplateRequest $request, UpsertWebsiteTemplate $upsert): RedirectResponse
    {
        $template = $upsert->handle($request->validated(), $request->user());

        flash(__('Template created.'))->success();

        return redirect()->route('admin.templates.edit', $template);
    }

    public function edit(WebsiteTemplate $websiteTemplate): View
    {
        return $this->form($websiteTemplate);
    }

    public function update(WebsiteTemplateRequest $request, WebsiteTemplate $websiteTemplate, UpsertWebsiteTemplate $upsert): RedirectResponse
    {
        $template = $upsert->handle($request->validated(), $request->user(), $websiteTemplate);

        flash(__('Template updated.'))->success();

        return redirect()->route('admin.templates.edit', $template);
    }

    public function destroy(WebsiteTemplate $websiteTemplate): RedirectResponse
    {
        $websiteTemplate->delete();

        flash(__('Template deleted.'))->success();

        return redirect()->route('admin.templates.index');
    }

    public function generate(WebsiteTemplate $websiteTemplate, TemplateScaffoldGenerator $generator): RedirectResponse
    {
        $paths = $generator->generate($websiteTemplate);

        $websiteTemplate->forceFill([
            'stylesheet_path' => $paths['stylesheet_path'],
            'view_path' => $paths['view_path'],
            'asset_path' => $paths['asset_path'],
        ])->save();

        flash(__('Template folders generated.'))->success();

        return redirect()->route('admin.templates.edit', $websiteTemplate);
    }

    private function form(WebsiteTemplate $template): View
    {
        return view('admin.templates.edit', [
            'template' => $template,
            'action' => $template->exists
                ? route('admin.templates.update', $template)
                : route('admin.templates.store'),
            'method' => $template->exists ? 'put' : 'post',
            'deleteAction' => $template->exists ? route('admin.templates.destroy', $template) : null,
        ]);
    }
}
