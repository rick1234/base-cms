<?php

namespace App\Support\Localization;

use Illuminate\Contracts\Translation\Loader;

class DatabaseTranslationLoader implements Loader
{
    public function __construct(
        private readonly Loader $loader,
        private readonly TranslationRepository $translations,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function load($locale, $group, $namespace = null): array
    {
        $fileLines = $this->loader->load($locale, $group, $namespace);
        $databaseLines = $this->translations->lines((string) $locale, (string) $group, $namespace);

        return array_replace_recursive($fileLines, $databaseLines);
    }

    public function addNamespace($namespace, $hint): void
    {
        $this->loader->addNamespace($namespace, $hint);
    }

    public function addJsonPath($path): void
    {
        $this->loader->addJsonPath($path);
    }

    /**
     * @return array<string, string>
     */
    public function namespaces(): array
    {
        return $this->loader->namespaces();
    }
}
