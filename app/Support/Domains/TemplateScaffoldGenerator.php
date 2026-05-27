<?php

namespace App\Support\Domains;

use App\Models\Cms\WebsiteTemplate;
use Illuminate\Filesystem\Filesystem;

class TemplateScaffoldGenerator
{
    public function __construct(
        private readonly Filesystem $files,
        private readonly TemplatePaths $paths,
    ) {}

    /**
     * @return array<string, string>
     */
    public function generate(WebsiteTemplate $template): array
    {
        $paths = $this->paths->forHandle((string) $template->handle);
        $viewPath = base_path($paths['view_path']);
        $scssPath = base_path($paths['scss_path']);
        $assetPath = base_path($paths['asset_path']);

        foreach ([$viewPath, $viewPath.'/partials', $scssPath, $assetPath.'/assets'] as $path) {
            $this->files->ensureDirectoryExists($path);
        }

        $this->putIfMissing($viewPath.'/README.md', $this->readme($template));
        $this->putIfMissing($viewPath.'/partials/socials.blade.php', $this->socialsPartial());
        $this->putIfMissing($scssPath.'/_index.scss', $this->scssIndex($template));
        $this->putIfMissing($assetPath.'/assets/.gitkeep', '');

        return $paths;
    }

    private function putIfMissing(string $path, string $contents): void
    {
        if ($this->files->exists($path)) {
            return;
        }

        $this->files->put($path, $contents);
    }

    private function readme(WebsiteTemplate $template): string
    {
        return <<<MD
# {$template->name}

This folder is reserved for Blade partials and layout overrides for the `{$template->handle}` website template.

Keep template-specific markup here, shared CMS templates in `resources/views/frontend`, and forked website behavior in `resources/views/site`.
MD;
    }

    private function socialsPartial(): string
    {
        return <<<'BLADE'
@props(['links' => []])

@if (count($links) > 0)
    <nav class="site-socials" aria-label="{{ __('Social links') }}">
        @foreach ($links as $link)
            <a class="site-social-link" href="{{ $link['url'] }}" rel="noopener noreferrer" target="_blank">
                {{ $link['label'] ?? ucfirst($link['platform'] ?? __('Social')) }}
            </a>
        @endforeach
    </nav>
@endif
BLADE;
    }

    private function scssIndex(WebsiteTemplate $template): string
    {
        return <<<SCSS
/*
 * Template: {$template->name} ({$template->handle})
 *
 * Domain colors, fonts, button radius, and content width are emitted as CSS custom properties
 * by the domain theme stylesheet route. Put structural template Sass here.
 */

.site-shell {
  min-height: 100dvh;
}
SCSS;
    }
}
