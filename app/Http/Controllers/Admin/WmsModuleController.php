<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\WmsModuleRecordRequest;
use App\Support\Wms\WmsModuleRegistry;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class WmsModuleController extends Controller
{
    public function index(WmsModuleRegistry $modules): View
    {
        return view('admin.wms.index', [
            'groups' => config('wms.groups'),
            'modulesByGroup' => $modules->groupedScreens(),
            'routeNames' => $this->routeNames(),
        ]);
    }

    public function show(string $module, WmsModuleRegistry $modules): View
    {
        return $this->listing($modules->find($module), $modules);
    }

    public function create(string $module, WmsModuleRegistry $modules): View
    {
        $definition = $modules->find($module);

        abort_unless($modules->isEditable($definition), 403);

        return $this->form($definition, null, $modules);
    }

    public function store(string $module, WmsModuleRecordRequest $request, WmsModuleRegistry $modules): RedirectResponse
    {
        $definition = $modules->find($module);
        $id = $modules->createRecord($definition, $request->validated(), $request->user()?->id);

        return $this->redirectToEdit($definition, $id)
            ->with('status', __('Record created.'));
    }

    public function edit(string $module, int $record, WmsModuleRegistry $modules): View
    {
        $definition = $modules->find($module);

        abort_unless($modules->isEditable($definition), 403);

        $row = $modules->findRecord($definition, $record);

        abort_unless($row, 404);

        return $this->form($definition, $row, $modules);
    }

    public function update(string $module, int $record, WmsModuleRecordRequest $request, WmsModuleRegistry $modules): RedirectResponse
    {
        $definition = $modules->find($module);
        $modules->updateRecord($definition, $record, $request->validated(), $request->user()?->id);

        return $this->redirectToEdit($definition, $record)
            ->with('status', __('Record updated.'));
    }

    public function destroy(string $module, int $record, Request $request, WmsModuleRegistry $modules): RedirectResponse
    {
        $definition = $modules->find($module);
        $modules->deleteRecord($definition, $record, $request->user()?->id);

        return redirect()
            ->route($this->routeNames()['show'], $this->routeParameter($definition))
            ->with('status', __('Record deleted.'));
    }

    public function legacyIndex(string $modulePath, WmsModuleRegistry $modules): View
    {
        return $this->listing($modules->find($modulePath), $modules);
    }

    public function legacyCreate(string $modulePath, WmsModuleRegistry $modules): View
    {
        return $this->create($modulePath, $modules);
    }

    public function legacyEdit(string $modulePath, Request $request, WmsModuleRegistry $modules): View
    {
        $definition = $modules->findPage($modulePath, 'edit');

        return $this->legacyFormOrUtility($definition, $request, $modules);
    }

    public function legacySave(string $modulePath, WmsModuleRecordRequest $request, WmsModuleRegistry $modules): RedirectResponse
    {
        return $this->saveLegacyPage($modules->findPage($modulePath, 'edit'), $request, $modules);
    }

    public function legacyPage(string $modulePath, string $page, Request $request, WmsModuleRegistry $modules): View
    {
        $definition = $modules->findPage($modulePath, $page);

        if ($definition['page_type'] === 'index') {
            return $this->listing($definition, $modules);
        }

        if ($definition['page_type'] === 'edit') {
            return $this->legacyFormOrUtility($definition, $request, $modules);
        }

        return $this->utility($definition, $modules);
    }

    public function legacyPageSave(string $modulePath, string $page, WmsModuleRecordRequest $request, WmsModuleRegistry $modules): RedirectResponse
    {
        return $this->saveLegacyPage($modules->findPage($modulePath, $page), $request, $modules);
    }

    public function legacyRootPage(string $page, WmsModuleRegistry $modules): View
    {
        $pageKey = str($page)->beforeLast('.php')->toString();
        $rootPage = config("wms.root_pages.$pageKey");

        abort_unless(is_array($rootPage), 404);

        return view('admin.wms.utility', [
            'module' => [
                'name' => $rootPage['name'],
                'description' => __('Legacy root WMS page recreated as a safe Laravel screen.'),
                'legacy_path' => 'wms/'.$pageKey.'.php',
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
            'backUrl' => route('wms.index'),
        ]);
    }

    public function legacyDelete(Request $request, WmsModuleRegistry $modules): RedirectResponse|View
    {
        $module = $request->string('module')->toString();
        $legacyClass = $request->string('obj_class')->toString();
        $record = $request->integer('id') ?: $request->integer('obj_id');

        if ($module !== '' && $record > 0) {
            $definition = $modules->find($module);
            $modules->deleteRecord($definition, $record, $request->user()?->id);

            return redirect()
                ->route('wms.modules.index', $definition['folder'])
                ->with('status', __('Record deleted.'));
        }

        if ($legacyClass !== '' && $record > 0 && $definition = $modules->findLegacyClass($legacyClass)) {
            $modules->deleteRecord($definition, $record, $request->user()?->id);

            return redirect()
                ->route('wms.modules.index', $definition['folder'])
                ->with('status', __('Record deleted.'));
        }

        return $this->legacyRootPage('delete.php', $modules);
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    private function listing(array $definition, WmsModuleRegistry $modules): View
    {
        return view('admin.wms.show', [
            'module' => $definition,
            'pages' => $modules->pagesFor($definition),
            'columns' => $modules->columns($definition),
            'rows' => $modules->rows($definition),
            'routeNames' => $this->routeNames(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    private function utility(array $definition, WmsModuleRegistry $modules): View
    {
        return view('admin.wms.utility', [
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
    private function legacyFormOrUtility(array $definition, Request $request, WmsModuleRegistry $modules): View
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
    private function form(array $definition, ?object $row, WmsModuleRegistry $modules): View
    {
        return view('admin.wms.form', [
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
    private function saveLegacyPage(array $definition, WmsModuleRecordRequest $request, WmsModuleRegistry $modules): RedirectResponse
    {
        $id = $request->integer('id');

        if ($id > 0 && $modules->findRecord($definition, $id)) {
            $modules->updateRecord($definition, $id, $request->validated(), $request->user()?->id);

            return redirect()
                ->route('wms.modules.page', [$definition['folder'], $definition['page_key'], 'id' => $id])
                ->with('status', __('Record updated.'));
        }

        $id = $modules->createRecord($definition, $request->validated(), $request->user()?->id);

        return redirect()
            ->route('wms.modules.page', [$definition['folder'], $definition['page_key'], 'id' => $id])
            ->with('status', __('Record created.'));
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    private function formAction(array $definition, ?object $row): string
    {
        if (request()->routeIs('wms.*') && isset($definition['page_key'])) {
            return route('wms.modules.page-save', [
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
        if (request()->routeIs('wms.*') && isset($definition['page_key'])) {
            return 'post';
        }

        return $row ? 'put' : 'post';
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    private function redirectToEdit(array $definition, int $id): RedirectResponse
    {
        if (request()->routeIs('wms.*')) {
            return redirect()->route($this->routeNames()['edit'], [$this->routeParameter($definition), 'id' => $id]);
        }

        return redirect()->route($this->routeNames()['edit'], [$this->routeParameter($definition), $id]);
    }

    /**
     * @return array<string, string>
     */
    private function routeNames(): array
    {
        if (request()->routeIs('wms.*')) {
            return [
                'index' => 'wms.index',
                'show' => 'wms.modules.index',
                'create' => 'wms.modules.create',
                'store' => 'wms.modules.store',
                'edit' => 'wms.modules.legacy-edit',
                'update' => 'wms.modules.update',
                'destroy' => 'wms.modules.destroy',
                'page' => 'wms.modules.page',
            ];
        }

        return [
            'index' => 'admin.wms.index',
            'show' => 'admin.wms.show',
            'create' => 'admin.wms.records.create',
            'store' => 'admin.wms.records.store',
            'edit' => 'admin.wms.records.edit',
            'update' => 'admin.wms.records.update',
            'destroy' => 'admin.wms.records.destroy',
            'page' => 'admin.wms.show',
        ];
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    private function routeParameter(array $definition): string
    {
        return request()->routeIs('wms.*') ? $definition['folder'] : $definition['handle'];
    }
}
