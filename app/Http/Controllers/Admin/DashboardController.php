<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cms\Module;
use App\Models\Cms\Page;
use App\Support\Wms\WmsModuleRegistry;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function __invoke(WmsModuleRegistry $wmsModules): View
    {
        return view('admin.dashboard', [
            'publishedPageCount' => Page::query()->published()->count(),
            'draftPageCount' => Page::query()->where('status', 'draft')->count(),
            'enabledModuleCount' => Module::query()->where('enabled', true)->count(),
            'wmsModuleCount' => $wmsModules->modules()->count(),
        ]);
    }
}
