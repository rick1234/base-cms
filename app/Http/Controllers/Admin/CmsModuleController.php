<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CmsModuleRecordRequest;
use App\Support\Cms\CmsModuleRegistry;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class CmsModuleController extends Controller
{
    public function index(CmsModuleRegistry $modules): View
    {
        return view('admin.cms.index', [
            'groups' => collect(config('cms_modules.groups'))
                ->map(fn (string $label): string => __($label))
                ->all(),
            'modulesByGroup' => $modules->groupedScreens(),
            'routeNames' => $this->routeNames(),
        ]);
    }

    public function show(string $module, CmsModuleRegistry $modules): View
    {
        return $this->listing($modules->find($module), $modules);
    }

    public function create(string $module, CmsModuleRegistry $modules): View
    {
        $definition = $modules->find($module);

        abort_unless($modules->isEditable($definition), 403);

        return $this->form($definition, null, $modules);
    }

    public function store(string $module, CmsModuleRecordRequest $request, CmsModuleRegistry $modules): RedirectResponse
    {
        $definition = $modules->find($module);
        $id = $modules->createRecord($definition, $request->validated(), $request->user()?->id);

        flash(__('Record created.'))->success();

        return $this->redirectToEdit($definition, $id);
    }

    public function edit(string $module, int $record, CmsModuleRegistry $modules): View
    {
        $definition = $modules->find($module);

        abort_unless($modules->isEditable($definition), 403);

        $row = $modules->findRecord($definition, $record);

        abort_unless($row, 404);

        return $this->form($definition, $row, $modules);
    }

    public function update(string $module, int $record, CmsModuleRecordRequest $request, CmsModuleRegistry $modules): RedirectResponse
    {
        $definition = $modules->find($module);
        $modules->updateRecord($definition, $record, $request->validated(), $request->user()?->id);

        flash(__('Record saved.'))->success();

        return $this->redirectToEdit($definition, $record);
    }

    public function destroy(string $module, int $record, Request $request, CmsModuleRegistry $modules): RedirectResponse
    {
        $definition = $modules->find($module);
        $modules->deleteRecord($definition, $record, $request->user()?->id);

        flash(__('Record deleted.'))->success();

        return redirect()
            ->route($this->routeNames()['show'], $this->routeParameter($definition));
    }

    public function legacyIndex(string $modulePath, CmsModuleRegistry $modules): View
    {
        return $this->listing($modules->find($modulePath), $modules);
    }

    public function legacyCreate(string $modulePath, CmsModuleRegistry $modules): View
    {
        return $this->create($modulePath, $modules);
    }

    public function legacyEdit(string $modulePath, Request $request, CmsModuleRegistry $modules): View
    {
        $definition = $modules->findPage($modulePath, 'edit');

        return $this->legacyFormOrUtility($definition, $request, $modules);
    }

    public function legacySave(string $modulePath, CmsModuleRecordRequest $request, CmsModuleRegistry $modules): RedirectResponse
    {
        return $this->saveLegacyPage($modules->findPage($modulePath, 'edit'), $request, $modules);
    }

    public function legacyPage(string $modulePath, string $page, Request $request, CmsModuleRegistry $modules): View
    {
        if ($nestedDefinition = $this->nestedFolderDefinition($modulePath, $page, $modules)) {
            return $this->listing($nestedDefinition, $modules);
        }

        $definition = $modules->findPage($modulePath, $page);

        if ($definition['page_type'] === 'index') {
            return $this->listing($definition, $modules);
        }

        if ($definition['page_type'] === 'edit') {
            return $this->legacyFormOrUtility($definition, $request, $modules);
        }

        return $this->utility($definition, $modules);
    }

    public function legacyPageSave(string $modulePath, string $page, CmsModuleRecordRequest $request, CmsModuleRegistry $modules): RedirectResponse
    {
        if ($this->nestedFolderDefinition($modulePath, $page, $modules)) {
            return $this->store(trim($modulePath.'/'.$page, '/'), $request, $modules);
        }

        return $this->saveLegacyPage($modules->findPage($modulePath, $page), $request, $modules);
    }

    public function legacyRootPage(string $page, CmsModuleRegistry $modules): View
    {
        $pageKey = str($page)->beforeLast('.php')->toString();
        $rootPage = config("cms_modules.root_pages.$pageKey");

        abort_unless(is_array($rootPage), 404);

        return view('admin.cms.utility', [
            'module' => [
                'name' => __($rootPage['name']),
                'description' => __('Legacy root CMS page recreated as a safe Laravel screen.'),
                'legacy_path' => 'cms/'.$pageKey.'.php',
                'legacy_tables' => [],
                'table' => '',
                'folder' => '',
                'handle' => $pageKey,
                'read_only' => true,
                'page_type' => $rootPage['type'] ?? 'utility',
            ],
            'pages' => collect(),
            'columns' => [],
            'rows' => new LengthAwarePaginator([], 0, 25),
            'routeNames' => $this->routeNames(),
            'backUrl' => route($this->routeNames()['index']),
        ]);
    }

    public function legacyDelete(Request $request, CmsModuleRegistry $modules): RedirectResponse|View
    {
        $module = $request->string('module')->toString();
        $legacyClass = $request->string('obj_class')->toString();
        $record = $request->integer('id') ?: $request->integer('obj_id');

        if ($module !== '' && $record > 0) {
            $definition = $modules->find($module);
            $modules->deleteRecord($definition, $record, $request->user()?->id);

            flash(__('Record deleted.'))->success();

            return redirect()
                ->route($this->routeNames()['show'], $definition['folder']);
        }

        if ($legacyClass !== '' && $record > 0 && $definition = $modules->findLegacyClass($legacyClass)) {
            $modules->deleteRecord($definition, $record, $request->user()?->id);

            flash(__('Record deleted.'))->success();

            return redirect()
                ->route($this->routeNames()['show'], $definition['folder']);
        }

        return $this->legacyRootPage('delete.php', $modules);
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    private function listing(array $definition, CmsModuleRegistry $modules): View
    {
        return view('admin.cms.show', [
            'module' => $definition,
            'pages' => $modules->pagesFor($definition),
            'columns' => $modules->columns($definition),
            'rows' => $modules->rows($definition),
            'routeNames' => $this->routeNames(),
            'emptyStateMessage' => $this->emptyStateMessage($definition),
        ]);
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    private function emptyStateMessage(array $definition): string
    {
        $label = $this->emptyStateLabel($definition);

        return __('No :module found.', [
            'module' => Str::of(__($label))->lower()->toString(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    private function emptyStateLabel(array $definition): string
    {
        return match ($definition['table'] ?? null) {
            'catalog_products' => 'Products',
            'faq_items' => 'FAQ items',
            'guestbook_entries' => 'Guestbook entries',
            'navigation_menus' => 'Navigation menus',
            'redirects' => 'Redirects',
            'translation_keys' => 'Translations',
            default => (string) ($definition['empty_label'] ?? $definition['name'] ?? 'records'),
        };
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    private function utility(array $definition, CmsModuleRegistry $modules): View
    {
        return view('admin.cms.utility', [
            'module' => $definition,
            'pages' => $modules->pagesFor($definition),
            'columns' => $modules->columns($definition),
            'rows' => $modules->rows($definition),
            'routeNames' => $this->routeNames(),
            'backUrl' => route($this->routeNames()['show'], $this->routeParameter($definition)),
        ]);
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    private function legacyFormOrUtility(array $definition, Request $request, CmsModuleRegistry $modules): View
    {
        if (! $modules->isEditable($definition)) {
            return $this->utility($definition, $modules);
        }

        $id = $request->integer('id');
        $row = $id > 0 ? $modules->findRecord($definition, $id) : null;

        return $this->form($definition, $row, $modules);
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    private function form(array $definition, ?object $row, CmsModuleRegistry $modules): View
    {
        return view('admin.cms.form', [
            'module' => $definition,
            'pages' => $modules->pagesFor($definition),
            'columns' => $modules->editableColumns($definition),
            'record' => $row,
            'action' => $this->formAction($definition, $row),
            'method' => $this->formMethod($definition, $row),
            'routeNames' => $this->routeNames(),
            'backUrl' => route($this->routeNames()['show'], $this->routeParameter($definition)),
            'deleteAction' => $row ? route($this->routeNames()['destroy'], [$this->routeParameter($definition), $row->id]) : null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    private function saveLegacyPage(array $definition, CmsModuleRecordRequest $request, CmsModuleRegistry $modules): RedirectResponse
    {
        $id = $request->integer('id');

        if ($id > 0 && $modules->findRecord($definition, $id)) {
            $modules->updateRecord($definition, $id, $request->validated(), $request->user()?->id);

            flash(__('Record saved.'))->success();

            return redirect()
                ->route($this->routeNames()['page'], [$definition['folder'], $definition['page_key'], 'id' => $id]);
        }

        $id = $modules->createRecord($definition, $request->validated(), $request->user()?->id);

        flash(__('Record created.'))->success();

        return redirect()
            ->route($this->routeNames()['page'], [$definition['folder'], $definition['page_key'], 'id' => $id]);
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    private function formAction(array $definition, ?object $row): string
    {
        if ($this->usesFolderRoutes() && isset($definition['page_key'])) {
            return route($this->routeNames()['page-save'], [
                $definition['folder'],
                $definition['page_key'],
                'id' => $row?->id,
            ]);
        }

        if ($row) {
            return route($this->routeNames()['update'], [$this->routeParameter($definition), $row->id]);
        }

        return route($this->routeNames()['store'], $this->routeParameter($definition));
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    private function formMethod(array $definition, ?object $row): string
    {
        if ($this->usesFolderRoutes() && isset($definition['page_key'])) {
            return 'post';
        }

        return $row ? 'put' : 'post';
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    private function redirectToEdit(array $definition, int $id): RedirectResponse
    {
        if (request()->routeIs('cms.*') && $this->usesFolderRoutes()) {
            return redirect()->route($this->routeNames()['edit'], [$this->routeParameter($definition), 'id' => $id]);
        }

        return redirect()->route($this->routeNames()['edit'], [$this->routeParameter($definition), $id]);
    }

    /**
     * @return array<string, string>
     */
    private function routeNames(): array
    {
        if (request()->routeIs('cms.*')) {
            return [
                'index' => 'cms.index',
                'show' => 'cms.modules.index',
                'create' => 'cms.modules.create',
                'store' => 'cms.modules.store',
                'edit' => 'cms.modules.legacy-edit',
                'update' => 'cms.modules.update',
                'destroy' => 'cms.modules.destroy',
                'page' => 'cms.modules.page',
                'page-save' => 'cms.modules.page-save',
            ];
        }

        return [
            'index' => 'admin.modules.index',
            'show' => 'admin.modules.show',
            'create' => 'admin.modules.create',
            'store' => 'admin.modules.store',
            'edit' => 'admin.modules.edit',
            'update' => 'admin.modules.update',
            'destroy' => 'admin.modules.destroy',
            'page' => 'admin.modules.page',
            'page-save' => 'admin.modules.page-save',
        ];
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    private function routeParameter(array $definition): string
    {
        return $this->usesFolderRoutes() ? $definition['folder'] : $definition['handle'];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function nestedFolderDefinition(string $modulePath, string $page, CmsModuleRegistry $modules): ?array
    {
        $pageKey = str($page)->beforeLast('.php')->toString();
        $currentDefinition = $modules->findOptional($modulePath);
        $combinedDefinition = $modules->findOptional(trim($modulePath.'/'.$pageKey, '/'));

        if (! $combinedDefinition) {
            return null;
        }

        if ($currentDefinition && isset(($currentDefinition['pages'] ?? [])[$pageKey])) {
            return null;
        }

        return $combinedDefinition;
    }

    private function usesFolderRoutes(): bool
    {
        return request()->routeIs('cms.*') || request()->routeIs('admin.modules.*');
    }
}
