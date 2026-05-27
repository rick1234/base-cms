<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Cms\CmsRedirect;
use App\Models\Cms\ContentItem;
use App\Models\Cms\Page;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PageController extends Controller
{
    public function __invoke(Request $request): View|RedirectResponse
    {
        $slug = (string) $request->route('slug');

        $page = Page::query()
            ->published()
            ->where('slug', $slug)
            ->first();

        if ($page instanceof Page) {
            return view('frontend.pages.show', [
                'page' => $page,
            ]);
        }

        $contentItem = ContentItem::query()
            ->online()
            ->where('slug', $slug)
            ->where('locale', app()->getLocale())
            ->first()
            ?? ContentItem::query()
                ->online()
                ->where('slug', $slug)
                ->whereNull('locale')
                ->first();

        if ($contentItem instanceof ContentItem) {
            return view('frontend.content.show', [
                'contentItem' => $contentItem,
                'page' => $contentItem,
            ]);
        }

        $redirect = CmsRedirect::findForPath($request->path());

        abort_unless($redirect, 404);

        $redirect->recordHit();

        return redirect()->to($redirect->targetForRequest($request), $redirect->status_code);
    }
}
