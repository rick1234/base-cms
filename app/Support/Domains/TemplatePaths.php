<?php

namespace App\Support\Domains;

use Illuminate\Support\Str;

class TemplatePaths
{
    /**
     * @return array{stylesheet_path: string, view_path: string, asset_path: string, scss_path: string}
     */
    public function forHandle(string $handle): array
    {
        $handle = $this->technicalName($handle);

        return [
            'stylesheet_path' => "resources/scss/site/templates/{$handle}/_index.scss",
            'view_path' => "resources/views/site/templates/{$handle}",
            'asset_path' => "public/site/templates/{$handle}",
            'scss_path' => "resources/scss/site/templates/{$handle}",
        ];
    }

    public function technicalName(string $handle): string
    {
        return Str::of($handle)
            ->lower()
            ->replaceMatches('/[^a-z0-9_-]/', '-')
            ->trim('-_')
            ->toString();
    }
}
