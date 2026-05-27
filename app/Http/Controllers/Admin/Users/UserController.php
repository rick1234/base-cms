<?php

namespace App\Http\Controllers\Admin\Users;

use App\Actions\Admin\Users\UpsertUser;
use App\Http\Controllers\Admin\Concerns\UsesEditViewForCreate;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Users\UserActionRequest;
use App\Http\Requests\Admin\Users\UserRequest;
use App\Models\User;
use App\Models\Cms\UserCategory;
use App\Models\Cms\CmsLanguage;
use App\Models\Cms\CmsRole;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;

class UserController extends Controller
{
    use UsesEditViewForCreate;

    public function index(Request $request): View
    {
        $categories = $this->categories();
        $categoryId = $request->integer('categoryId');
        $query = User::query()
            ->with(['categories', 'role', 'roles'])
            ->withCount('categories');

        if ($request->filled('id')) {
            $query->whereKey($request->integer('id'));
        }

        if ($request->filled('username')) {
            $username = $request->string('username')->toString();
            $query->where(function ($query) use ($username): void {
                $query
                    ->where('username', 'like', '%'.$username.'%')
                    ->orWhere('name', 'like', '%'.$username.'%')
                    ->orWhere('email', 'like', '%'.$username.'%');
            });
        }

        if ($request->filled('bedrijfsnaam') || $request->filled('company_name')) {
            $query->where('company_name', 'like', '%'.$request->input('company_name', $request->input('bedrijfsnaam')).'%');
        }

        $this->applyStatusFilter($query, $request->input('status'));

        if ($categoryId > 0) {
            $categoryIds = [$categoryId];

            if ($request->boolean('showChild')) {
                $categoryIds = [...$categoryIds, ...$this->descendantIds($categories, $categoryId)];
            }

            $query->whereHas('categories', fn ($categoryQuery) => $categoryQuery->whereIn('user_categories.id', $categoryIds));
        }

        $users = $query
            ->orderBy($this->sortColumn($request->string('sort')->toString()), $request->string('sorttype')->lower()->toString() === 'asc' ? 'asc' : 'desc')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'categories' => $categories,
            'selectedCategoryId' => $categoryId,
            'showChild' => $request->boolean('showChild'),
            'routeNames' => $this->routeNames(),
        ]);
    }

    public function store(UserRequest $request, UpsertUser $upsert): RedirectResponse
    {
        $user = $upsert->handle($request->validated(), $request->user(), null, $this->uploadedImage($request));

        flash(__('User created.'))->success();

        return redirect()
            ->route($this->routeName('edit'), ['id' => $user->id]);
    }

    public function edit(Request $request): View
    {
        $user = $this->userFromRequest($request);
        $user?->load(['categories', 'role', 'roles', 'creator', 'updater']);

        return view('admin.users.edit', [
            'managedUser' => $user ?? new User([
                'locale' => app()->getLocale(),
                'active_from' => now(),
                'is_active' => true,
                'is_admin' => false,
            ]),
            'categories' => $this->categories(),
            'languages' => CmsLanguage::query()->enabled()->orderByDesc('is_default')->orderBy('name')->get(),
            'roles' => CmsRole::query()->where('status', 'active')->orderBy('name')->get(),
            'routeNames' => $this->routeNames(),
            'pageName' => __('Edit user'),
            'backUrl' => route($this->routeName('index')),
            'deleteAction' => $user ? route($this->routeName('destroy'), $user) : null,
        ]);
    }

    public function save(UserRequest $request, UpsertUser $upsert): RedirectResponse
    {
        $user = $upsert->handle($request->validated(), $request->user(), $request->userRecord(), $this->uploadedImage($request));

        flash(__('User saved.'))->success();

        return redirect()
            ->route($this->routeName('edit'), ['id' => $user->id]);
    }

    public function update(User $user, UserRequest $request, UpsertUser $upsert): RedirectResponse
    {
        $user = $upsert->handle($request->validated(), $request->user(), $user, $this->uploadedImage($request));

        flash(__('User saved.'))->success();

        return redirect()
            ->route($this->routeName('edit'), ['id' => $user->id]);
    }

    public function destroy(User $user, Request $request): RedirectResponse
    {
        if ($request->user()?->is($user)) {
            flash(__('You cannot delete your own user account.'))->error();

            return redirect()->route($this->routeName('index'));
        }

        $user->delete();

        flash(__('User deleted.'))->success();

        return redirect()->route($this->routeName('index'));
    }

    public function deleteImage(UserActionRequest $request, UpsertUser $upsert): JsonResponse|RedirectResponse
    {
        $user = User::query()->findOrFail($request->integer('id'));
        $upsert->deleteImage($user);

        if (! $request->expectsJson()) {
            flash(__('Image deleted.'))->success();

            return back();
        }

        return response()->json(['status' => 'success']);
    }

    private function userFromRequest(Request $request): ?User
    {
        $id = (int) ($request->route('id') ?: $request->integer('id'));

        return $id > 0 ? User::query()->findOrFail($id) : null;
    }

    /**
     * @return Collection<int, UserCategory>
     */
    private function categories(): Collection
    {
        return UserCategory::query()
            ->withCount('users')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * @param  Collection<int, UserCategory>  $categories
     * @return list<int>
     */
    private function descendantIds(Collection $categories, int $parentId): array
    {
        $ids = [];

        foreach ($categories->where('parent_id', $parentId) as $category) {
            $ids[] = $category->id;
            $ids = [...$ids, ...$this->descendantIds($categories, $category->id)];
        }

        return $ids;
    }

    private function sortColumn(string $sort): string
    {
        return match ($sort) {
            'user', 'username' => 'username',
            'naam', 'name' => 'name',
            'email' => 'email',
            'bedrijfsnaam', 'company_name' => 'company_name',
            'status' => 'is_active',
            default => 'id',
        };
    }

    private function applyStatusFilter($query, mixed $status): void
    {
        match ((string) $status) {
            '2', 'active', '1-active' => $query->where('is_active', true),
            '3', 'inactive', '0' => $query->where('is_active', false),
            default => null,
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
            'destroy' => $this->routeName('destroy'),
            'image.delete' => $this->routeName('image.delete'),
        ];
    }

    private function routeName(string $name): string
    {
        return (request()->routeIs('cms.*') ? 'cms.users.' : 'admin.users.').$name;
    }

    private function uploadedImage(UserRequest $request): ?UploadedFile
    {
        $file = $request->file('image') ?: $request->file('afbeelding');

        return $file instanceof UploadedFile ? $file : null;
    }
}
