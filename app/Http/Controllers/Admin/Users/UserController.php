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
use App\Models\Cms\Country;
use App\Support\Auth\TwoFactorAuthenticator;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;

class UserController extends Controller
{
    use UsesEditViewForCreate;

    public function index(Request $request): View
    {
        $categories = $this->categories();
        $categoryId = $request->integer('categoryId');
        $query = User::query()
            ->select('users.*')
            ->selectSub(
                DB::table('user_logins')
                    ->select('created_at')
                    ->whereColumn('user_logins.user_id', 'users.id')
                    ->orderByDesc('login_date')
                    ->orderByDesc('login_time')
                    ->orderByDesc('created_at')
                    ->limit(1),
                'last_login_at'
            )
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
                    ->orWhere('email', 'like', '%'.$username.'%');
            });
        }

        if ($request->filled('name')) {
            $name = $request->string('name')->toString();
            $query->where(function ($query) use ($name): void {
                $query
                    ->where('name', 'like', '%'.$name.'%')
                    ->orWhere('first_name', 'like', '%'.$name.'%')
                    ->orWhere('middle_name', 'like', '%'.$name.'%')
                    ->orWhere('last_name', 'like', '%'.$name.'%');
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
        $requestedTab = $request->route('tab');
        $activeTab = $user && in_array($requestedTab, ['access', 'roles', 'image', 'two-factor'], true) ? $requestedTab : 'profile';

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
            'countries' => Country::query()->enabled()->orderBy('name')->get(),
            'routeNames' => $this->routeNames(),
            'pageName' => __('Edit user'),
            'backUrl' => route($this->routeName('index')),
            'deleteAction' => $user ? route($this->routeName('destroy'), $user) : null,
            'activeTab' => $activeTab,
            'twoFactorQrSvg' => $user?->two_factor_secret ? app(TwoFactorAuthenticator::class)->qrCodeSvg($user, $user->two_factor_secret) : null,
            'twoFactorProvisioningUri' => $user?->two_factor_secret ? app(TwoFactorAuthenticator::class)->provisioningUri($user, $user->two_factor_secret) : null,
        ]);
    }

    public function save(UserRequest $request, UpsertUser $upsert): RedirectResponse
    {
        $user = $upsert->handle($request->validated(), $request->user(), $request->userRecord(), $this->uploadedImage($request));

        flash(__('User saved.'))->success();

        return $this->redirectToUserEdit($user, $request);
    }

    public function update(User $user, UserRequest $request, UpsertUser $upsert): RedirectResponse
    {
        $user = $upsert->handle($request->validated(), $request->user(), $user, $this->uploadedImage($request));

        flash(__('User saved.'))->success();

        return $this->redirectToUserEdit($user, $request);
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

    public function impersonate(User $user, Request $request): RedirectResponse
    {
        $currentUser = $request->user();

        if (! $currentUser || $currentUser->is($user)) {
            flash(__('You are already logged in as this user.'))->warning();

            return back();
        }

        if (! $user->hasAdminPermission('admin.access')) {
            flash(__('This user cannot access the admin area.'))->error();

            return back();
        }

        if (! $request->session()->has('admin_impersonator_id')) {
            $request->session()->put('admin_impersonator_id', $currentUser->id);
        }

        auth()->login($user);
        $request->session()->regenerate();

        flash(__('Logged in as :name.', ['name' => $user->displayName()]))->success();

        return redirect()->route('admin.dashboard');
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

    public function sendInvitation(Request $request, User $user, string $area): RedirectResponse
    {
        abort_unless(in_array($area, ['frontend', 'backend'], true), 404);

        if (! $user->email) {
            flash(__('This user does not have an email address.'))->error();

            return back();
        }

        if ($area === 'backend' && ! $user->hasAdminPermission('admin.access')) {
            flash(__('Enable backend access or assign a role with backend access before sending the backend invitation.'))->error();

            return back();
        }

        $status = Password::sendResetLink(['email' => $user->email]);

        if ($status !== Password::RESET_LINK_SENT) {
            flash(__($status))->error();

            return back();
        }

        flash($area === 'backend' ? __('Backend invitation sent.') : __('Frontend invitation sent.'))->success();

        return back();
    }

    public function generateTwoFactor(User $user, Request $request, TwoFactorAuthenticator $twoFactor): RedirectResponse
    {
        $user->forceFill([
            'two_factor_secret' => $twoFactor->generateSecret(),
            'two_factor_confirmed_at' => now(),
            'two_factor_disabled_at' => null,
            'updated_by' => $request->user()?->id,
        ])->save();

        flash(__('Two-factor secret generated.'))->success();

        return redirect()->route($this->routeName('edit.tab'), ['id' => $user->id, 'tab' => 'two-factor']);
    }

    public function disableTwoFactor(User $user, Request $request): RedirectResponse
    {
        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_confirmed_at' => null,
            'two_factor_disabled_at' => now(),
            'updated_by' => $request->user()?->id,
        ])->save();

        flash(__('Two-factor authentication disabled.'))->success();

        return redirect()->route($this->routeName('edit.tab'), ['id' => $user->id, 'tab' => 'two-factor']);
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
            'last_login', 'last_login_at' => 'last_login_at',
            default => 'id',
        };
    }

    private function applyStatusFilter(Builder $query, mixed $status): void
    {
        match ((string) $status) {
            '2', 'active', '1-active' => $query->where('is_active', true),
            '3', 'inactive', '0' => $query->where('is_active', false),
            '4', 'revoked' => $query->whereNotNull('active_until')->whereDate('active_until', '<', now()),
            default => null,
        };
    }

    private function redirectToUserEdit(User $user, Request $request): RedirectResponse
    {
        $activeTab = $request->string('active_tab')->toString();

        if (in_array($activeTab, ['access', 'roles', 'image', 'two-factor'], true)) {
            return redirect()->route($this->routeName('edit.tab'), ['id' => $user->id, 'tab' => $activeTab]);
        }

        return redirect()->route($this->routeName('edit'), ['id' => $user->id]);
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
            'edit.tab' => $this->routeName('edit.tab'),
            'save' => $this->routeName('save'),
            'destroy' => $this->routeName('destroy'),
            'impersonate' => $this->routeName('impersonate'),
            'invitation' => $this->routeName('invitation'),
            'two-factor.generate' => $this->routeName('two-factor.generate'),
            'two-factor.disable' => $this->routeName('two-factor.disable'),
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
