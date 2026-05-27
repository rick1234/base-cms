<?php

namespace App\Http\Middleware;

use App\Models\Cms\Domain;
use App\Support\Localization\TranslationRepository;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function __construct(private readonly TranslationRepository $translations) {}

    public function handle(Request $request, Closure $next): Response
    {
        $enabledLanguages = $this->enabledLanguagesForRequest($request);
        $locale = $this->resolveLocale($request, $enabledLanguages);

        App::setLocale($locale);
        app('translator')->setLoaded([]);
        View::share('currentLocale', $locale);
        View::share('enabledLocales', $enabledLanguages);

        return $next($request);
    }

    /**
     * @param  Collection<int, mixed>  $enabledLanguages
     */
    private function resolveLocale(Request $request, Collection $enabledLanguages): string
    {
        $routeLocale = $request->route('locale');
        $sessionLocale = $request->session()->get('locale');
        $userLocale = $request->user()?->locale;
        $defaultLocale = $this->domainDefaultLocale($enabledLanguages) ?: $this->translations->defaultLocale();

        foreach ([$routeLocale, $sessionLocale, $userLocale, $defaultLocale] as $candidate) {
            if (! is_string($candidate) || blank($candidate)) {
                continue;
            }

            $locale = $this->translations->normalizeLocale($candidate);

            if ($this->isEnabledLocale($locale, $enabledLanguages)) {
                return $locale;
            }
        }

        $firstEnabledLocale = $enabledLanguages->first()?->code;

        return is_string($firstEnabledLocale)
            ? $this->translations->normalizeLocale($firstEnabledLocale)
            : $defaultLocale;
    }

    /**
     * @return Collection<int, mixed>
     */
    private function enabledLanguagesForRequest(Request $request): Collection
    {
        $languages = $this->translations->enabledLanguages();
        $domain = app()->bound('cms.active_domain') ? app('cms.active_domain') : null;

        if (! $domain instanceof Domain) {
            return $languages;
        }

        $activeLocales = $this->translations->activeArea() === 'admin'
            ? $domain->activeBackendLocales()
            : $domain->activeFrontendLocales();

        $normalizedLocales = collect($activeLocales)
            ->map(fn (string $locale): string => $this->translations->normalizeLocale($locale))
            ->all();

        $filtered = $languages->filter(fn ($language): bool => in_array(
            $this->translations->normalizeLocale((string) $language->code),
            $normalizedLocales,
            true,
        ))->values();

        return $filtered;
    }

    /**
     * @param  Collection<int, mixed>  $enabledLanguages
     */
    private function isEnabledLocale(string $locale, Collection $enabledLanguages): bool
    {
        return $enabledLanguages
            ->pluck('code')
            ->map(fn (string $code): string => $this->translations->normalizeLocale($code))
            ->contains($this->translations->normalizeLocale($locale));
    }

    /**
     * @param  Collection<int, mixed>  $enabledLanguages
     */
    private function domainDefaultLocale(Collection $enabledLanguages): ?string
    {
        $domain = app()->bound('cms.active_domain') ? app('cms.active_domain') : null;

        if (! $domain instanceof Domain || ! $domain->default_locale) {
            return null;
        }

        $locale = $this->translations->normalizeLocale($domain->default_locale);

        return $this->isEnabledLocale($locale, $enabledLanguages) ? $locale : null;
    }
}
