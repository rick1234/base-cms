<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Cms\Page;
use App\Models\Cms\RedirectRule;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PageController extends Controller
{
    public function __invoke(Request $request, string $slug): View|RedirectResponse
    {
        $page = Page::query()
            ->published()
            ->where('slug', $slug)
            ->first();

        if ($page instanceof Page) {
            return view('frontend.pages.show', [
                'page' => $page,
            ]);
        }

        $redirect = RedirectRule::findForPath($request->path());

        abort_unless($redirect, 404);

        return redirect()->to($redirect->target_url, $redirect->status_code);
    }
}
