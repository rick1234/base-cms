<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Admin\Dashboard\DashboardNavigationBuilder;
use App\Support\Admin\Dashboard\DashboardRecentItems;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;

class DashboardController extends Controller
{
    public function __invoke(DashboardNavigationBuilder $dashboardNavigation, DashboardRecentItems $recentItems): View
    {
        $usesLegacyRoutes = request()->routeIs('cms.*');
        $user = request()->user();

        return view('admin.dashboard', [
            'dashboardModules' => $dashboardNavigation->moduleList($usesLegacyRoutes),
            'recentItems' => $recentItems->lists($usesLegacyRoutes),
            'currentUser' => $user,
            'currentUserInitials' => $this->initials((string) ($user?->fullName() ?: $user?->displayName() ?: $user?->email)),
            'currentUserSettingsUrl' => $user ? $this->userSettingsUrl((int) $user->id, $usesLegacyRoutes) : null,
        ]);
    }

    private function userSettingsUrl(int $userId, bool $usesLegacyRoutes): string
    {
        if ($usesLegacyRoutes) {
            return route('cms.users.edit', ['id' => $userId]);
        }

        return route('admin.users.edit', ['id' => $userId]);
    }

    private function initials(string $name): string
    {
        $initials = Str::of($name)
            ->replaceMatches('/[^\pL\pN ]+/u', ' ')
            ->explode(' ')
            ->filter()
            ->take(2)
            ->map(fn (string $part): string => Str::of($part)->substr(0, 1)->upper()->toString())
            ->implode('');

        return $initials !== '' ? $initials : 'U';
    }
}
