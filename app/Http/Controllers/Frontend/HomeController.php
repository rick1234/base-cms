<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Cms\Page;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $page = Page::query()
            ->published()
            ->where('slug', 'home')
            ->first()
            ?? Page::query()->published()->ordered()->first();

        return view('frontend.pages.home', [
            'page' => $page,
        ]);
    }
}
