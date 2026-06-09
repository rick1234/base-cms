<?php

namespace App\Support\Media;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Format;
use Intervention\Image\ImageManager;

class ImageVariantFactory
{
    private const ALLOWED_FORMATS = ['avif', 'gif', 'jpg', 'jpeg', 'png', 'webp'];

    public function url(
        Model|string|null $source,
        ?int $width = null,
        ?int $height = null,
        bool $crop = false,
        ?string $format = null,
        ?string $fallbackName = null,
    ): ?string {
        $path = $this->sourcePath($source);

        if ($path === null) {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        $sourceFile = $this->absoluteSourcePath($path);

        if ($sourceFile === null || ! is_file($sourceFile)) {
            return $this->publicUrl($path);
        }

        $width = $width !== null ? max(1, $width) : null;
        $height = $height !== null ? max(1, $height) : null;
        $format = $this->normalizeFormat($format, $sourceFile);
        $variantPath = $this->variantPath($sourceFile, $source, $width, $height, $crop, $format, $fallbackName);
        $disk = Storage::disk((string) config('cms_images.cache_disk', 'public'));

        if (! $disk->exists($variantPath)) {
            $this->makeVariant($sourceFile, $disk->path($variantPath), $width, $height, $crop, $format);
        }

        return $disk->url($variantPath);
    }

    private function sourcePath(Model|string|null $source): ?string
    {
        if ($source instanceof Model) {
            $path = $source->getAttribute('image_path') ?? $source->getAttribute('path');

            return is_string($path) && trim($path) !== '' ? trim($path) : null;
        }

        return is_string($source) && trim($source) !== '' ? trim($source) : null;
    }

    private function absoluteSourcePath(string $path): ?string
    {
        $path = trim($path);

        if (Str::startsWith($path, 'storage/')) {
            return public_path($path);
        }

        if (is_file(public_path($path))) {
            return public_path($path);
        }

        $storagePath = Storage::disk('public')->path(ltrim($path, '/'));

        return is_file($storagePath) ? $storagePath : null;
    }

    private function publicUrl(string $path): string
    {
        if (Str::startsWith($path, 'storage/')) {
            return asset($path);
        }

        if (is_file(public_path($path))) {
            return asset($path);
        }

        return Storage::disk('public')->url(ltrim($path, '/'));
    }

    private function normalizeFormat(?string $format, string $sourceFile): string
    {
        $format = strtolower(trim((string) ($format ?: config('cms_images.default_format', 'webp'))));
        $format = $format === 'jpeg' ? 'jpg' : $format;

        if (in_array($format, self::ALLOWED_FORMATS, true)) {
            return $format;
        }

        $extension = strtolower(pathinfo($sourceFile, PATHINFO_EXTENSION));

        return in_array($extension, self::ALLOWED_FORMATS, true) ? ($extension === 'jpeg' ? 'jpg' : $extension) : 'webp';
    }

    private function variantPath(
        string $sourceFile,
        Model|string|null $source,
        ?int $width,
        ?int $height,
        bool $crop,
        string $format,
        ?string $fallbackName,
    ): string {
        $mtime = (string) (@filemtime($sourceFile) ?: '0');
        $hash = substr(sha1($sourceFile.'|'.$mtime), 0, 16);
        $slug = $this->seoName($source, $sourceFile, $fallbackName);
        $size = ($width ?: 'auto').'x'.($height ?: 'auto');
        $mode = $crop ? 'crop' : 'fit';
        $extension = $format === 'jpeg' ? 'jpg' : $format;

        return trim((string) config('cms_images.cache_directory', 'image-cache'), '/')
            ."/{$hash}/{$slug}-{$size}-{$mode}.{$extension}";
    }

    private function seoName(Model|string|null $source, string $sourceFile, ?string $fallbackName): string
    {
        $candidates = [$fallbackName];

        if ($source instanceof Model) {
            foreach (['alt_text', 'title_text', 'caption', 'name', 'title', 'original_filename'] as $field) {
                $candidates[] = $source->getAttribute($field);
            }
        } elseif (is_string($source)) {
            $candidates[] = basename($source);
        }

        $candidates[] = basename($sourceFile);

        foreach ($candidates as $candidate) {
            if (! is_string($candidate) || trim($candidate) === '') {
                continue;
            }

            $slug = Str::slug(pathinfo($candidate, PATHINFO_FILENAME) ?: $candidate);

            if ($slug !== '') {
                return Str::limit($slug, 80, '');
            }
        }

        return 'image';
    }

    private function makeVariant(string $sourceFile, string $targetFile, ?int $width, ?int $height, bool $crop, string $format): void
    {
        $directory = dirname($targetFile);

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $manager = ImageManager::usingDriver(GdDriver::class);
        $image = $manager->decodePath($sourceFile);

        if ($width !== null && $height !== null && $crop) {
            $image->coverDown($width, $height);
        } elseif ($width !== null || $height !== null) {
            $image->scaleDown($width, $height);
        }

        $encoded = $image->encodeUsingFormat($this->interventionFormat($format), ...$this->encoderOptions($format));
        $encoded->save($targetFile);
    }

    private function interventionFormat(string $format): Format
    {
        return match ($format) {
            'avif' => Format::AVIF,
            'gif' => Format::GIF,
            'jpg', 'jpeg' => Format::JPEG,
            'png' => Format::PNG,
            default => Format::WEBP,
        };
    }

    /**
     * @return array<string, int>
     */
    private function encoderOptions(string $format): array
    {
        return match ($format) {
            'avif' => ['quality' => (int) config('cms_images.quality.avif', 55), 'strip' => true],
            'jpg', 'jpeg' => ['quality' => (int) config('cms_images.quality.jpg', 82), 'progressive' => true, 'strip' => true],
            'png' => ['indexed' => (bool) config('cms_images.quality.png_indexed', false)],
            'webp' => ['quality' => (int) config('cms_images.quality.webp', 82), 'strip' => true],
            default => [],
        };
    }
}
