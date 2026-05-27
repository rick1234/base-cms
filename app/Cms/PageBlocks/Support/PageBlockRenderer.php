<?php

namespace App\Cms\PageBlocks\Support;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class PageBlockRenderer
{
    public static function sanitizedHtml(mixed $html): HtmlString
    {
        if (is_array($html)) {
            $html = collect($html)->flatten()->filter(fn (mixed $value): bool => is_string($value))->implode(' ');
        }

        return new HtmlString((string) str($html ?? '')->sanitizeHtml());
    }

    public static function mediaUrl(mixed $path): ?string
    {
        if (! is_string($path) || trim($path) === '') {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        if (Str::startsWith($path, 'storage/')) {
            return asset($path);
        }

        if (is_file(public_path($path))) {
            return asset($path);
        }

        return Storage::disk('public')->url($path);
    }

    public static function safeUrl(mixed $url): ?string
    {
        if (! is_string($url) || trim($url) === '') {
            return null;
        }

        $url = trim($url);

        if (Str::startsWith($url, '/')) {
            return $url;
        }

        $scheme = parse_url($url, PHP_URL_SCHEME);

        if (in_array($scheme, ['http', 'https', 'mailto', 'tel'], true)) {
            return $url;
        }

        return null;
    }

    public static function blockWidth(mixed $layout): int
    {
        if (is_int($layout) || is_float($layout) || (is_string($layout) && preg_match('/^\d+$/', trim($layout)) === 1)) {
            return max(0, min(100, (int) round(((float) $layout) / 5) * 5));
        }

        return match ((string) $layout) {
            'full' => 100,
            'half' => 50,
            'one-third' => 35,
            'two-thirds' => 65,
            'one-quarter' => 25,
            default => 100,
        };
    }

    public static function videoEmbedUrl(mixed $url, mixed $provider = 'auto'): ?string
    {
        if (! is_string($url) || trim($url) === '') {
            return null;
        }

        $provider = is_string($provider) ? $provider : 'auto';

        if (($provider === 'youtube' || $provider === 'auto')
            && preg_match('/(?:youtube\.com\/(?:watch\?v=|embed\/|shorts\/)|youtu\.be\/)([A-Za-z0-9_-]{6,})/', $url, $matches) === 1) {
            return 'https://www.youtube-nocookie.com/embed/'.$matches[1];
        }

        if (($provider === 'vimeo' || $provider === 'auto')
            && preg_match('/vimeo\.com\/(?:video\/)?([0-9]+)/', $url, $matches) === 1) {
            return 'https://player.vimeo.com/video/'.$matches[1];
        }

        return null;
    }

    /**
     * @return list<string>
     */
    public static function captions(mixed $captionNotes): array
    {
        if (! is_string($captionNotes)) {
            return [];
        }

        return collect(preg_split('/\r\n|\r|\n/', $captionNotes) ?: [])
            ->map(fn (string $caption): string => trim($caption))
            ->values()
            ->all();
    }
}
