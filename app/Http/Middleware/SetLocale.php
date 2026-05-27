<?php

namespace App\Http\Middleware;

use App\Support\Localization\TranslationRepository;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function __construct(private readonly TranslationRepository $translations) {}

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->resolveLocale($request);

        App::setLocale($locale);
        app('translator')->setLoaded([]);
        View::share('currentLocale', $locale);
        View::share('enabledLocales', $this->translations->enabledLanguages());

        return $next($request);
    }

    private function resolveLocale(Request $request): string
    {
        $routeLocale = $request->route('locale');
        $sessionLocale = $request->session()->get('locale');
        $userLocale = $request->user()?->locale;
        $defaultLocale = $this->translations->defaultLocale();

        foreach ([$routeLocale, $sessionLocale, $userLocale, $defaultLocale] as $candidate) {
            if (! is_string($candidate) || blank($candidate)) {
                continue;
            }

            $locale = $this->translations->normalizeLocale($candidate);

            if ($this->translations->isEnabledLocale($locale)) {
                return $locale;
            }
        }

        return $defaultLocale;
    }
}
