<?php

namespace App\Support\Domains;

use App\Models\Cms\Domain;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class DomainResolver
{
    public const CONTAINER_KEY = 'cms.active_domain';
    public const PREVIEW_SESSION_KEY = 'cms.preview_domain_id';

    public function resolve(Request $request): ?Domain
    {
        if (! $this->hasDomainTable()) {
            return null;
        }

        if ($previewDomain = $this->previewDomain($request)) {
            return $previewDomain;
        }

        $host = Domain::normalizeHost($request->getHost());

        if ($host === '') {
            return $this->developmentFallback($request);
        }

        $domain = $this->findByHost($host);

        if (! $domain && str_starts_with($host, 'www.')) {
            $domain = $this->findByHost(Domain::hostWithoutWww($host));
        }

        return $domain ?: $this->developmentFallback($request);
    }

    /**
     * @return Collection<int, Domain>
     */
    public function previewDomains(): Collection
    {
        if (! $this->hasDomainTable()) {
            return collect();
        }

        try {
            return Domain::query()
                ->active()
                ->with('template')
                ->ordered()
                ->get();
        } catch (QueryException) {
            return collect();
        }
    }

    public function isDevelopmentRequest(Request $request): bool
    {
        if (! App::environment(['local', 'testing'])) {
            return false;
        }

        $host = Domain::normalizeHost($request->getHost());

        foreach (config('cms_domains.development_host_suffixes', []) as $suffix) {
            if (Str::endsWith($host, $suffix)) {
                return true;
            }
        }

        return in_array($host, ['localhost', '127.0.0.1'], true);
    }

    private function findByHost(string $host): ?Domain
    {
        try {
            return Domain::query()
                ->active()
                ->with(['template', 'contactForm.blocks.rows.fields.options'])
                ->where(function ($query) use ($host): void {
                    $query->where('host', $host)
                        ->orWhereHas('aliases', fn ($query) => $query->where('host', $host));
                })
                ->first();
        } catch (QueryException) {
            return null;
        }
    }

    private function previewDomain(Request $request): ?Domain
    {
        if (! $this->isDevelopmentRequest($request)) {
            return null;
        }

        $previewId = $request->session()->get(self::PREVIEW_SESSION_KEY);

        if (! is_numeric($previewId)) {
            return null;
        }

        try {
            return Domain::query()
                ->active()
                ->with(['template', 'contactForm.blocks.rows.fields.options'])
                ->find((int) $previewId);
        } catch (QueryException) {
            return null;
        }
    }

    private function developmentFallback(Request $request): ?Domain
    {
        if (! $this->isDevelopmentRequest($request)) {
            return null;
        }

        try {
            return Domain::query()
                ->active()
                ->with(['template', 'contactForm.blocks.rows.fields.options'])
                ->where('is_development', true)
                ->ordered()
                ->first()
                ?: Domain::query()
                    ->active()
                    ->with(['template', 'contactForm.blocks.rows.fields.options'])
                    ->ordered()
                    ->first();
        } catch (QueryException) {
            return null;
        }
    }

    private function hasDomainTable(): bool
    {
        try {
            return Schema::hasTable('domains')
                && Schema::hasColumn('domains', 'website_template_id');
        } catch (QueryException) {
            return false;
        }
    }
}
