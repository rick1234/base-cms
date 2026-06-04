<?php

namespace App\Http\Middleware;

use App\Models\Cms\Domain;
use App\Models\Cms\Page;
use App\Support\Domains\DomainResolver;
use App\Support\Navigation\FrontendNavigationBuilder;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class ResolveDomain
{
    public function __construct(
        private readonly DomainResolver $domains,
        private readonly FrontendNavigationBuilder $navigation,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $domain = $this->domains->resolve($request);
        $previewDomains = $this->domains->previewDomains();
        $isPreviewMode = $this->domains->isDevelopmentRequest($request) && $previewDomains->isNotEmpty();

        app()->instance(DomainResolver::CONTAINER_KEY, $domain);

        View::share('activeDomain', $domain);
        View::share('activeTemplate', $domain?->template);
        View::share('domainTemplateSettings', $domain?->effectiveTemplateSettings() ?? config('cms_domains.default_template_settings', []));
        View::share('domainPreviewDomains', $previewDomains);
        View::share('isDomainPreviewMode', $isPreviewMode);
        View::share('domainLocalHost', Domain::normalizeHost(config('cms_domains.local_domain')));
        View::share('frontendNavigationPages', $this->navigationPages($domain));
        $navigationLocale = $this->navigationLocale($request, $domain);

        View::share('frontendNavigationItems', $this->navigation->tree('primary', $domain, $navigationLocale));
        View::share('frontendFooterNavigationItems', $this->navigation->tree('footer', $domain, $navigationLocale));

        return $next($request);
    }

    /**
     * @return Collection<int, Page>
     */
    private function navigationPages(?Domain $domain): Collection
    {
        $globalPages = Page::query()
            ->published()
            ->whereNull('domain_id')
            ->ordered()
            ->get();

        if (! $domain instanceof Domain) {
            return $globalPages;
        }

        $domainPages = Page::query()
            ->published()
            ->where('domain_id', $domain->id)
            ->ordered()
            ->get();

        $domainSlugs = $domainPages->pluck('slug')->all();

        return $domainPages
            ->concat($globalPages->reject(fn (Page $page): bool => in_array($page->slug, $domainSlugs, true)))
            ->values();
    }

    private function navigationLocale(Request $request, ?Domain $domain): string
    {
        foreach ([
            $request->route('locale'),
            $request->session()->get('locale'),
            $request->user()?->locale,
            $domain?->default_locale,
            app()->getLocale(),
        ] as $candidate) {
            if (! is_string($candidate) || trim($candidate) === '') {
                continue;
            }

            return strtolower(str_replace('_', '-', trim($candidate)));
        }

        return strtolower((string) config('app.locale', 'en'));
    }
}
