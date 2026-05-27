<?php

namespace App\Actions\Admin\Domains;

use App\Models\Cms\Domain;
use App\Models\Cms\DomainAlias;
use App\Models\User;
use App\Support\Domains\DomainFaviconManager;
use App\Support\Domains\DomainLanguageActivator;
use App\Support\Domains\DomainWizard;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class UpsertDomain
{
    public function __construct(
        private readonly DomainFaviconManager $favicons,
        private readonly DomainLanguageActivator $languages,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data, ?User $user, ?Domain $domain = null, ?string $step = null): Domain
    {
        return DB::transaction(function () use ($data, $user, $domain, $step): Domain {
            $domain ??= new Domain;

            if (! $domain->exists) {
                $domain->created_by = $user?->id;
            }

            $step = is_string($step) ? DomainWizard::normalize($step) : null;

            if ($step === null) {
                $this->fillAll($domain, $data, $user);
            } else {
                $this->fillStep($domain, $data, $user, $step);
            }

            $domain->save();

            if ($step === null || $step === 'identity') {
                $this->syncAliases($domain, (array) ($data['aliases'] ?? []), $user);
            }

            $favicon = $data['favicon_logo'] ?? $data['favicon_svg'] ?? null;

            if (($step === null || $step === 'favicon') && $favicon instanceof UploadedFile) {
                $assets = $this->favicons->generateFromLogo($domain, $favicon);

                $domain->forceFill([
                    'favicon_svg_path' => $assets['source'] ?? $assets['svg'] ?? null,
                    'favicon_assets' => $assets,
                ])->save();
            }

            return $domain->refresh()->load(['aliases', 'template']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function fillAll(Domain $domain, array $data, ?User $user): void
    {
        $domain->fill([
            'host' => Domain::normalizeHost((string) $data['host']),
            'name' => $data['name'],
            'company_name' => $data['company_name'] ?? null,
            'website_template_id' => $data['website_template_id'] ?? null,
            'default_locale' => $data['default_locale'] ?? null,
            'title_separator' => $data['title_separator'] ?? '|',
            'canonical_base_url' => $data['canonical_base_url'] ?? null,
            'default_meta_title' => $data['default_meta_title'] ?? null,
            'default_meta_description' => $data['default_meta_description'] ?? null,
            'default_og_title' => $data['default_og_title'] ?? null,
            'default_og_description' => $data['default_og_description'] ?? null,
            'default_og_image' => $data['default_og_image'] ?? null,
            'robots' => $data['robots'] ?? null,
            'active_frontend_locales' => $this->frontendLocales($data, $user),
            'active_backend_locales' => $this->backendLocales($data, $user),
            'template_settings' => $this->templateSettings($data),
            'social_links' => $this->socialLinks((array) ($data['social_links'] ?? [])),
            'contact_form_id' => $data['contact_form_id'] ?? null,
            'public_integrations' => $this->publicIntegrations((array) ($data['public_integrations'] ?? [])),
            'integration_credentials' => $domain->integration_credentials,
            'is_active' => (bool) ($data['is_active'] ?? false),
            'is_development' => (bool) ($data['is_development'] ?? false),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'updated_by' => $user?->id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function fillStep(Domain $domain, array $data, ?User $user, string $step): void
    {
        match ($step) {
            'identity' => $domain->fill([
                'host' => Domain::normalizeHost((string) $data['host']),
                'name' => $data['name'],
                'company_name' => $data['company_name'] ?? null,
                'default_locale' => $data['default_locale'] ?? null,
                'is_active' => (bool) ($data['is_active'] ?? false),
                'is_development' => (bool) ($data['is_development'] ?? false),
                'sort_order' => (int) ($data['sort_order'] ?? 0),
                'updated_by' => $user?->id,
            ]),
            'languages' => $domain->fill([
                'active_frontend_locales' => $this->frontendLocales($data, $user),
                'active_backend_locales' => $this->backendLocales($data, $user),
                'updated_by' => $user?->id,
            ]),
            'template' => $domain->fill([
                'website_template_id' => $data['website_template_id'] ?? null,
                'template_settings' => $this->templateSettings($data),
                'updated_by' => $user?->id,
            ]),
            'seo' => $domain->fill([
                'title_separator' => $data['title_separator'] ?? '|',
                'canonical_base_url' => $data['canonical_base_url'] ?? null,
                'default_meta_title' => $data['default_meta_title'] ?? null,
                'default_meta_description' => $data['default_meta_description'] ?? null,
                'default_og_title' => $data['default_og_title'] ?? null,
                'default_og_description' => $data['default_og_description'] ?? null,
                'default_og_image' => $data['default_og_image'] ?? null,
                'robots' => $data['robots'] ?? null,
                'updated_by' => $user?->id,
            ]),
            'integrations' => $domain->fill([
                'public_integrations' => $this->publicIntegrations((array) ($data['public_integrations'] ?? [])),
                'updated_by' => $user?->id,
            ]),
            'social-contact' => $domain->fill([
                'social_links' => $this->socialLinks((array) ($data['social_links'] ?? [])),
                'contact_form_id' => $data['contact_form_id'] ?? null,
                'updated_by' => $user?->id,
            ]),
            default => $domain->fill([
                'updated_by' => $user?->id,
            ]),
        };
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function templateSettings(array $data): array
    {
        $settings = collect(config('cms_domains.default_template_settings', []))
            ->keys()
            ->mapWithKeys(function (string $key) use ($data): array {
                return [$key => data_get($data, "template_settings.{$key}")];
            })
            ->filter(fn (mixed $value): bool => $value !== null && $value !== '')
            ->all();

        return $settings;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<string>
     */
    private function frontendLocales(array $data, ?User $user): array
    {
        $locales = array_values((array) ($data['active_frontend_locales'] ?? []));
        $language = $this->languages->activateFromSearch($data['language_search'] ?? null, $user);

        if ($language && (bool) ($data['language_add_to_frontend'] ?? false)) {
            $locales[] = $language->code;
        }

        return $this->normalizeLocales($locales);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<string>
     */
    private function backendLocales(array $data, ?User $user): array
    {
        $locales = array_values((array) ($data['active_backend_locales'] ?? []));
        $language = $this->languages->activateFromSearch($data['language_search'] ?? null, $user);

        if ($language && (bool) ($data['language_add_to_backend'] ?? false)) {
            $locales[] = $language->code;
        }

        return $this->normalizeLocales($locales);
    }

    /**
     * @param  array<int, mixed>  $locales
     * @return list<string>
     */
    private function normalizeLocales(array $locales): array
    {
        return collect($locales)
            ->map(fn (mixed $locale): string => trim((string) $locale))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return list<array{platform: string, label: string|null, icon: string|null, url: string}>
     */
    private function socialLinks(array $rows): array
    {
        return collect($rows)
            ->map(function (array $row): ?array {
                $url = trim((string) ($row['url'] ?? ''));

                if ($url === '') {
                    return null;
                }

                return [
                    'platform' => trim((string) ($row['platform'] ?? 'link')),
                    'label' => filled($row['label'] ?? null) ? trim((string) $row['label']) : null,
                    'icon' => filled($row['icon'] ?? null) ? trim((string) $row['icon']) : null,
                    'url' => $url,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $integrations
     * @return array<string, string>
     */
    private function publicIntegrations(array $integrations): array
    {
        return collect(config('cms_domains.public_integrations', []))
            ->keys()
            ->mapWithKeys(fn (string $key): array => [$key => trim((string) ($integrations[$key] ?? ''))])
            ->filter()
            ->all();
    }

    /**
     * @param  list<string>  $aliases
     */
    private function syncAliases(Domain $domain, array $aliases, ?User $user): void
    {
        $localHost = Domain::normalizeHost(config('cms_domains.local_domain'));
        $normalizedAliases = collect($aliases)
            ->map(fn (string $host): string => Domain::normalizeHost($host))
            ->filter(fn (string $host): bool => $host !== '' && $host !== $domain->host)
            ->when(
                $localHost !== '' && $localHost !== $domain->host,
                fn ($aliases) => $aliases->push($localHost),
            )
            ->reject(fn (string $host): bool => $this->hostBelongsToAnotherDomain($host, $domain))
            ->unique()
            ->values();

        $domain->aliases()
            ->whereNotIn('host', $normalizedAliases->all())
            ->delete();

        $normalizedAliases->each(function (string $host) use ($domain, $user): void {
            $alias = $domain->aliases()->firstOrNew(['host' => $host]);

            if (! $alias->exists) {
                $alias->created_by = $user?->id;
            }

            $alias->forceFill([
                'is_primary' => false,
                'updated_by' => $user?->id,
            ])->save();
        });
    }

    private function hostBelongsToAnotherDomain(string $host, Domain $domain): bool
    {
        return Domain::query()
            ->whereKeyNot($domain->id)
            ->where('host', $host)
            ->exists()
            || DomainAlias::query()
                ->where('domain_id', '<>', $domain->id)
                ->where('host', $host)
                ->exists();
    }
}
