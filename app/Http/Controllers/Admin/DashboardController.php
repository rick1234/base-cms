<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Admin\Dashboard\DashboardNavigationBuilder;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function __invoke(DashboardNavigationBuilder $dashboardNavigation): View
    {
        return view('admin.dashboard', [
            'dashboardGroups' => $dashboardNavigation->build(request()->routeIs('cms.*')),
        ]);
    }
}
