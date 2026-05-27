<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Cms\Domain;
use App\Models\Cms\Page;
use App\Support\Domains\DomainResolver;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $domain = app()->bound(DomainResolver::CONTAINER_KEY)
            ? app(DomainResolver::CONTAINER_KEY)
            : null;

        $page = $this->firstForDomain(
            Page::query()
                ->published()
                ->where('slug', 'home'),
            $domain,
        )
            ?? $this->firstForDomain(Page::query()
                ->published()
                ->ordered()
                ->limit(1), $domain);

        return view('frontend.pages.home', [
            'page' => $page,
        ]);
    }

    /**
     * @template TModel of Model
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
