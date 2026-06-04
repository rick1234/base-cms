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

    public static function hasRenderableContent(mixed $block): bool
    {
        if (! is_array($block)) {
            return false;
        }

        $data = data_get($block, 'data', []);
        $settings = data_get($block, 'settings', []);

        return match ((string) data_get($block, 'type')) {
            'attachment' => self::mediaUrl(data_get($data, 'file')) !== null,
            'button' => self::hasText(data_get($data, 'label')) && self::safeUrl(data_get($data, 'url')) !== null,
            'gallery' => collect(data_get($data, 'images', []))->contains(fn (mixed $image): bool => self::mediaUrl($image) !== null),
            'image' => self::mediaUrl(data_get($data, 'image')) !== null,
            'quote' => self::hasText(data_get($data, 'quote')),
            'text' => self::hasText(data_get($data, 'content')),
            'title' => self::hasText(data_get($data, 'title')),
            'video' => self::videoEmbedUrl(data_get($data, 'video_url'), data_get($settings, 'provider')) !== null,
            default => true,
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

    private static function hasText(mixed $value): bool
    {
        if (is_array($value)) {
            $value = collect($value)
                ->flatten()
                ->filter(fn (mixed $item): bool => is_scalar($item))
                ->implode(' ');
        }

        $text = html_entity_decode(strip_tags((string) ($value ?? '')), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = str_replace("\xc2\xa0", ' ', $text);

        return trim($text) !== '';
    }
}
