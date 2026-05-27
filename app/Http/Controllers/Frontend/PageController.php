<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Cms\CmsRedirect;
use App\Models\Cms\ContentItem;
use App\Models\Cms\Domain;
use App\Models\Cms\Page;
use App\Support\Domains\DomainResolver;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PageController extends Controller
{
    public function __invoke(Request $request): View|RedirectResponse
    {
        $slug = (string) $request->route('slug');
        $domain = app()->bound(DomainResolver::CONTAINER_KEY)
            ? app(DomainResolver::CONTAINER_KEY)
            : null;

        $page = $this->firstForDomain(
            Page::query()
                ->published()
                ->where('slug', $slug),
            $domain,
        );

        if ($page instanceof Page) {
            return view('frontend.pages.show', [
                'page' => $page,
            ]);
        }

        $contentItem = $this->firstForDomain(
            ContentItem::query()
                ->online()
                ->where('slug', $slug)
                ->where('locale', app()->getLocale()),
            $domain,
        )
            ?? $this->firstForDomain(
                ContentItem::query()
                    ->online()
                    ->where('slug', $slug)
                    ->whereNull('locale'),
                $domain,
            );

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

    /**
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     * @return TModel|null
     */
    private function firstForDomain(Builder $query, mixed $domain): ?Model
    {
        if (! $domain instanceof Domain) {
            return $query->first();
        }

        return (clone $query)->where('domain_id', $domain->id)->first()
            ?: (clone $query)->whereNull('domain_id')->first();
    }
}
