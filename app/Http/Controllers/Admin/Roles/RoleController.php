<?php

namespace App\Http\Controllers\Admin\Roles;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Roles\RoleRequest;
use App\Models\Cms\CmsRole;
use App\Support\Admin\Roles\ModulePermissionRegistry;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class RoleController extends Controller
{
    public function index(Request $request, ModulePermissionRegistry $registry): View
    {
        $registry->syncPermissions();

        $query = CmsRole::query()
            ->withCount(['permissions', 'users']);

        if ($request->filled('id')) {
            $query->whereKey($request->integer('id'));
        }

        if ($request->filled('name') || $request->filled('naam')) {
            $query->where('name', 'like', '%'.$request->input('name', $request->input('naam')).'%');
        }

        if ($request->filled('status') || $request->filled('actief')) {
            $query->where('status', $this->normalizedStatus($request->input('status', $request->input('actief'))));
        }

        $roles = $query
            ->orderBy($this->sortColumn($request->string('sort')->toString()), $request->string('sorttype')->lower()->toString() === 'asc' ? 'asc' : 'desc')
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('admin.roles.index', [
            'roles' => $roles,
            'routeNames' => $this->routeNames(),
        ]);
    }

    public function store(RoleRequest $request, ModulePermissionRegistry $registry): RedirectResponse
    {
        $role = $this->saveRole($request, $registry);

        flash(__('Role created.'))->success();

        return redirect()->route($this->routeName('edit'), ['id' => $role->id]);
    }

    public function create(Request $request, ModulePermissionRegistry $registry): View
    {
        return $this->edit($request, $registry);
    }

    public function edit(Request $request, ModulePermissionRegistry $registry): View
    {
        $permissions = $registry->syncPermissions();
        $role = $this->roleFromRequest($request);
        $role?->load('permissions');

        return view('admin.roles.edit', [
            'role' => $role ?? new CmsRole([
                'status' => 'active',
            ]),
            'permissionGroups' => $registry->groupedModules(),
            'permissionsByKey' => $permissions,
            'selectedPermissionIds' => $role?->permissions->pluck('id')->all() ?? [],
            'routeNames' => $this->routeNames(),
            'pageName' => __('Edit role'),
            'backUrl' => route($this->routeName('index')),
            'deleteAction' => $role ? route($this->routeName('destroy'), $role) : null,
        ]);
    }

    public function save(RoleRequest $request, ModulePermissionRegistry $registry): RedirectResponse
    {
        $role = $this->saveRole($request, $registry, $request->role());

        flash(__('Role saved.'))->success();

        return redirect()->route($this->routeName('edit'), ['id' => $role->id]);
    }

    public function update(CmsRole $role, RoleRequest $request, ModulePermissionRegistry $registry): RedirectResponse
    {
        $role = $this->saveRole($request, $registry, $role);

        flash(__('Role saved.'))->success();

        return redirect()->route($this->routeName('edit'), ['id' => $role->id]);
    }

    public function destroy(CmsRole $role): RedirectResponse
    {
        if ($role->users()->exists()) {
            flash(__('This role is assigned to users and cannot be deleted.'))->error();

            return redirect()->route($this->routeName('index'));
        }

        $role->permissions()->detach();
        $role->delete();

        flash(__('Role deleted.'))->success();

        return redirect()->route($this->routeName('index'));
    }

    private function saveRole(RoleRequest $request, ModulePermissionRegistry $registry, ?CmsRole $role = null): CmsRole
    {
        $registry->syncPermissions();
        $role ??= new CmsRole;
        $attributes = Arr::only($request->validated(), [
            'name',
            'slug',
            'description',
            'status',
        ]);

        if (blank($attributes['slug'] ?? null)) {
            $attributes['slug'] = Str::slug((string) $attributes['name']);
        }

        if (! $role->exists) {
            $attributes['created_by'] = $request->user()?->getAuthIdentifier();
        }

        $attributes['updated_by'] = $request->user()?->getAuthIdentifier();

        $role->fill($attributes)->save();
        $role->syncPermissionIds($request->validated('permissions') ?? []);

        return $role->refresh();
    }

    private function roleFromRequest(Request $request): ?CmsRole
    {
        $id = (int) ($request->route('id') ?: $request->integer('id'));

        return $id > 0 ? CmsRole::query()->findOrFail($id) : null;
    }

    private function sortColumn(string $sort): string
    {
        return match ($sort) {
            'name', 'naam' => 'name',
            'status' => 'status',
            'permissions' => 'permissions_count',
            'users' => 'users_count',
            default => 'id',
        };
    }

    private function normalizedStatus(mixed $status): string
    {
        return match ((string) $status) {
            '1', 'active', 'published' => 'active',
            '0', '2', '3', 'inactive', 'draft' => 'inactive',
            default => 'active',
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
        ];
    }

    private function routeName(string $name): string
    {
        return (request()->routeIs('cms.*') ? 'cms.roles.' : 'admin.roles.').$name;
    }
}
