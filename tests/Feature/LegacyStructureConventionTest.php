<?php

namespace Tests\Feature;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Tests\TestCase;

class LegacyStructureConventionTest extends TestCase
{
    public function test_application_code_does_not_use_php_superglobals(): void
    {
        $this->assertNoMatches(
            ['app', 'routes', 'resources/views'],
            '/\$_(?:GET|POST|REQUEST|FILES|SERVER)\b/',
        );
    }

    public function test_admin_controllers_do_not_use_raw_sql(): void
    {
        $this->assertNoMatches(
            ['app/Http/Controllers'],
            '/\bDB::(?:select|statement|insert|update|delete)\b|->(?:selectRaw|whereRaw|orderByRaw)\s*\(/',
        );
    }

    public function test_blade_views_do_not_use_inline_style_attributes(): void
    {
        $this->assertNoMatches(
            ['resources/views'],
            '/\sstyle\s*=/i',
        );
    }

    /**
     * @param  list<string>  $roots
     */
    private function assertNoMatches(array $roots, string $pattern): void
    {
        $matches = [];

        foreach ($this->files($roots) as $file) {
            $contents = (string) file_get_contents($file->getPathname());

            if (preg_match($pattern, $contents) === 1) {
                $matches[] = str_replace(base_path().DIRECTORY_SEPARATOR, '', $file->getPathname());
            }
        }

        $this->assertSame([], $matches);
    }

    /**
     * @param  list<string>  $roots
     * @return list<SplFileInfo>
     */
    private function files(array $roots): array
    {
        $files = [];

        foreach ($roots as $root) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator(base_path($root), RecursiveDirectoryIterator::SKIP_DOTS),
            );

            foreach ($iterator as $file) {
                if ($file instanceof SplFileInfo && $file->isFile() && $this->isScannable($file)) {
                    $files[] = $file;
                }
            }
        }

        return $files;
    }

    private function isScannable(SplFileInfo $file): bool
    {
        return in_array($file->getExtension(), ['php'], true)
            || str_ends_with($file->getFilename(), '.blade.php');
    }
}
