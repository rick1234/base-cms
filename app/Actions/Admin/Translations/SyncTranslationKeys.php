<?php

namespace App\Actions\Admin\Translations;

use App\Models\Cms\TranslationKey;
use App\Support\Localization\TranslationRepository;
use Database\Seeders\TranslationModuleSeeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class SyncTranslationKeys
{
    public function __construct(private readonly TranslationRepository $translations) {}

    /**
     * @return array{created:int,updated:int,scanned:int}
     */
    public function handle(): array
    {
        $created = 0;
        $updated = 0;
        $scanned = 0;
        $sourceLocale = $this->translations->sourceLocale();
        $defaults = TranslationModuleSeeder::defaults();

        foreach ($this->translationStrings() as $area => $strings) {
            foreach ($strings as $string) {
                $scanned++;
                $sourceText = $defaults[$string]['en'] ?? $string;

                $translationKey = TranslationKey::query()->firstOrNew([
                    'area' => $area,
                    'group' => '*',
                    'key' => $string,
                ]);

                $translationKey->fill([
                    'source_text' => $sourceText,
                    'source_locale' => $sourceLocale,
                    'status' => 'active',
                    'is_system' => true,
                    'last_seen_at' => now(),
                ]);

                $translationKey->exists ? $updated++ : $created++;
                $translationKey->save();

                $translationKey->values()->updateOrCreate(
                    ['locale' => $sourceLocale],
                    [
                        'value' => $sourceText,
                        'status' => 'active',
                        'is_reviewed' => true,
                        'reviewed_at' => now(),
                    ],
                );
            }
        }

        return [
            'created' => $created,
            'updated' => $updated,
            'scanned' => $scanned,
        ];
    }

    /**
     * @return array<string, list<string>>
     */
    private function translationStrings(): array
    {
        $strings = [
            'admin' => [],
            'frontend' => [],
            'shared' => [],
        ];

        foreach ($this->filesToScan() as $file) {
            $contents = File::get($file);
            $area = $this->areaFor($file);

            foreach ($this->extractStrings($contents) as $string) {
                $strings[$area][] = $string;
            }
        }

        foreach ($this->cmsModuleStrings() as $string) {
            $strings['admin'][] = $string;
        }

        return collect($strings)
            ->map(fn (array $areaStrings): array => collect($areaStrings)
                ->filter()
                ->unique()
                ->sort()
                ->values()
                ->all())
            ->all();
    }

    /**
     * @return list<string>
     */
    private function cmsModuleStrings(): array
    {
        $strings = collect(config('cms_modules.groups', []))
            ->values();

        foreach (['modules', 'screens'] as $section) {
            collect(config("cms_modules.{$section}", []))
                ->each(function (array $definition) use ($strings): void {
                    $strings->push($definition['name'] ?? null);
                    $strings->push($definition['description'] ?? null);

                    collect($definition['pages'] ?? [])
                        ->each(fn (array $page) => $strings->push($page['name'] ?? null));
                });
        }

        collect(config('cms_modules.root_pages', []))
            ->each(fn (array $page) => $strings->push($page['name'] ?? null));

        collect(config('cms_modules.legacy_classes', []))
            ->keys()
            ->each(fn (string $legacyClass) => $strings->push($legacyClass));

        return $strings
            ->filter(fn (mixed $value): bool => is_string($value) && trim($value) !== '')
            ->map(fn (string $value): string => trim($value))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return list<string>
     */
    private function filesToScan(): array
    {
        return collect([
            resource_path('views'),
            app_path('Http/Controllers'),
            app_path('Http/Requests'),
            app_path('Mail'),
            base_path('routes'),
        ])
            ->filter(fn (string $path): bool => File::exists($path))
            ->flatMap(fn (string $path): array => File::allFiles($path))
            ->filter(fn ($file): bool => in_array($file->getExtension(), ['php'], true))
            ->map(fn ($file): string => $file->getPathname())
            ->all();
    }

    /**
     * @return list<string>
     */
    private function extractStrings(string $contents): array
    {
        preg_match_all("/(?:__|@lang)\\(\\s*(['\"])((?:\\\\.|(?!\\1).)*)\\1/u", $contents, $matches);

        return collect($matches[2] ?? [])
            ->map(fn (string $value): string => stripcslashes($value))
            ->filter(fn (string $value): bool => trim($value) !== '')
            ->values()
            ->all();
    }

    private function areaFor(string $file): string
    {
        $path = Str::of($file)->replace('\\', '/')->lower()->toString();

        if (str_contains($path, '/admin/') || str_contains($path, '/routes/admin.php') || str_contains($path, '/routes/cms.php')) {
            return 'admin';
        }

        if (str_contains($path, '/frontend/') || str_contains($path, '/routes/web.php') || str_contains($path, '/resources/views/site/')) {
            return 'frontend';
        }

        return 'shared';
    }
}
