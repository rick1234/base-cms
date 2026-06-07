<?php

namespace App\Http\Controllers\Admin\Domains;

use App\Actions\Admin\Domains\UpsertWebsiteTemplate;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Domains\WebsiteTemplateRequest;
use App\Models\Cms\WebsiteTemplate;
use App\Support\Domains\TemplateScaffoldGenerator;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class WebsiteTemplateController extends Controller
{
    /**
     * @var list<string>
     */
    private const EDIT_TABS = ['settings', 'sections', 'paths', 'preview'];

    public function index(): View
    {
        return view('admin.templates.index');
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

    public function update(WebsiteTemplateRequest $request, WebsiteTemplate $websiteTemplate, UpsertWebsiteTemplate $upsert): RedirectResponse
    {
        $template = $upsert->handle($this->dataWithExistingValues($request, $websiteTemplate), $request->user(), $websiteTemplate);

        flash(__('Template saved.'))->success();

        return redirect()->route($this->editRouteName($request->input('active_tab')), $this->editRouteParameters($template, $request->input('active_tab')));
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

    public function edit(WebsiteTemplate $websiteTemplate, Request $request): View
    {
        return $this->form($websiteTemplate, $this->activeTab($request, $websiteTemplate));
    }

    private function form(WebsiteTemplate $template, string $activeTab = 'identity'): View
    {
        return view('admin.templates.edit', [
            'template' => $template,
            'activeTab' => $template->exists ? $activeTab : 'identity',
            'action' => $template->exists
                ? route('admin.templates.update', $template)
                : route('admin.templates.store'),
            'method' => $template->exists ? 'put' : 'post',
            'deleteAction' => $template->exists ? route('admin.templates.destroy', $template) : null,
        ]);
    }

    private function activeTab(Request $request, WebsiteTemplate $template): string
    {
        $tab = $request->route('tab') ?: $request->query('tab');

        if (! $template->exists || ! is_string($tab) || ! in_array($tab, self::EDIT_TABS, true)) {
            return 'identity';
        }

        return $tab;
    }

    /**
     * @return array<string, mixed>
     */
    private function dataWithExistingValues(WebsiteTemplateRequest $request, WebsiteTemplate $template): array
    {
        $data = $request->validated();

        foreach (['stylesheet_path', 'asset_path', 'view_path'] as $field) {
            if (! array_key_exists($field, $data)) {
                $data[$field] = $template->{$field};
            }
        }

        if (! array_key_exists('default_settings', $data)) {
            $data['default_settings'] = $template->default_settings ?? [];
        }

        if (! array_key_exists('defined_sections', $data)) {
            $data['defined_sections'] = $template->defined_sections ?? [];
        }

        if (! $request->has('is_active')) {
            $data['is_active'] = (bool) $template->is_active;
        }

        return $data;
    }

    private function editRouteName(mixed $tab): string
    {
        return is_string($tab) && in_array($tab, self::EDIT_TABS, true)
            ? 'admin.templates.edit.tab'
            : 'admin.templates.edit';
    }

    /**
     * @return array<string, mixed>
     */
    private function editRouteParameters(WebsiteTemplate $template, mixed $tab): array
    {
        $parameters = ['websiteTemplate' => $template];

        if (is_string($tab) && in_array($tab, self::EDIT_TABS, true)) {
            $parameters['tab'] = $tab;
        }

        return $parameters;
    }
}
