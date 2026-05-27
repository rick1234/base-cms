<?php

namespace App\Support\Domains;

class GoogleFontUrl
{
    /**
     * @param  array<string, mixed>  $settings
     * @return list<string>
     */
    public function stylesheetUrls(array $settings): array
    {
        return collect([
            $settings['base_font_google_url'] ?? null,
            $settings['heading_font_google_url'] ?? null,
        ])
            ->map(fn (mixed $value): ?string => $this->sanitize($value))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public function sanitize(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        $value = trim($value);

        if (! $this->isValid($value)) {
            return null;
        }

        return $value;
    }

    public function isValid(mixed $value): bool
    {
        if (! is_string($value) || trim($value) === '') {
            return false;
        }

        $parts = parse_url(trim($value));

        if (($parts['scheme'] ?? null) !== 'https') {
            return false;
        }

        if (($parts['host'] ?? null) !== 'fonts.googleapis.com') {
            return false;
        }

        if (! in_array($parts['path'] ?? '', ['/css', '/css2'], true)) {
            return false;
        }

        return $this->firstFamily($value) !== null;
    }

    public function familyStack(mixed $value, string $fallbackGeneric = 'sans-serif'): ?string
    {
        $family = $this->firstFamily($value);

        if ($family === null) {
            return null;
        }

        $fallbackGeneric = in_array($fallbackGeneric, ['serif', 'sans-serif', 'monospace'], true)
            ? $fallbackGeneric
            : 'sans-serif';

        return '"'.str_replace('"', '', $family).'", '.$fallbackGeneric;
    }

    private function firstFamily(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        $query = parse_url(trim($value), PHP_URL_QUERY);

        if (! is_string($query) || $query === '') {
            return null;
        }

        foreach (explode('&', $query) as $part) {
            [$key, $rawFamily] = array_pad(explode('=', $part, 2), 2, null);

            if ($key !== 'family' || ! is_string($rawFamily)) {
                continue;
            }

            $family = rawurldecode(str_replace('+', ' ', $rawFamily));
            $family = trim(explode(':', $family, 2)[0]);
            $family = preg_replace('/\s+/', ' ', $family);

            if (is_string($family) && preg_match('/^[\pL\pN .\'-]+$/u', $family) === 1) {
                return $family;
            }
        }

        return null;
    }
}
