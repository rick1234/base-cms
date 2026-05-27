<?php

namespace App\Actions\Admin\Domains;

use App\Models\Cms\WebsiteTemplate;
use App\Models\User;
use App\Support\Domains\TemplatePaths;
use Illuminate\Support\Facades\DB;

class UpsertWebsiteTemplate
{
    public function __construct(private readonly TemplatePaths $paths) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data, ?User $user, ?WebsiteTemplate $template = null): WebsiteTemplate
    {
        return DB::transaction(function () use ($data, $user, $template): WebsiteTemplate {
            $template ??= new WebsiteTemplate;

            if (! $template->exists) {
                $template->created_by = $user?->id;
            }

            $technicalName = $this->paths->technicalName((string) $data['handle']);
            $defaultPaths = $this->paths->forHandle($technicalName);
            $previousDefaults = $template->exists ? $this->paths->forHandle((string) $template->handle) : [];

            $template->fill([
                'handle' => $technicalName,
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'stylesheet_path' => $this->pathValue($data, 'stylesheet_path', $defaultPaths, $previousDefaults),
                'asset_path' => $this->pathValue($data, 'asset_path', $defaultPaths, $previousDefaults),
                'view_path' => $this->pathValue($data, 'view_path', $defaultPaths, $previousDefaults),
                'default_settings' => $this->settings($data),
                'is_active' => (bool) ($data['is_active'] ?? false),
                'sort_order' => (int) ($data['sort_order'] ?? 0),
                'updated_by' => $user?->id,
            ]);

            $template->save();

            return $template->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, string>  $defaultPaths
     * @param  array<string, string>  $previousDefaults
     */
    private function pathValue(array $data, string $field, array $defaultPaths, array $previousDefaults): string
    {
        $submitted = trim((string) ($data[$field] ?? ''));

        if ($submitted === '') {
            return $defaultPaths[$field];
        }

        if (($previousDefaults[$field] ?? null) === $submitted) {
            return $defaultPaths[$field];
        }

        return $submitted;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function settings(array $data): array
    {
        $settings = (array) ($data['default_settings'] ?? []);

        return collect(config('cms_domains.default_template_settings', []))
            ->keys()
            ->mapWithKeys(fn (string $key): array => [$key => $settings[$key] ?? null])
            ->filter(fn (mixed $value): bool => $value !== null && $value !== '')
            ->all();
    }

}
