<?php

namespace App\Support\Domains;

use App\Models\Cms\Domain;
use GdImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class DomainFaviconManager
{
    /**
     * @return array<string, string>
     */
    public function generateFromLogo(Domain $domain, UploadedFile $file): array
    {
        return $this->isSvg($file)
            ? $this->generateFromSvg($domain, $file)
            : $this->generateFromRaster($domain, $file);
    }

    /**
     * @return array<string, string>
     */
    private function generateFromSvg(Domain $domain, UploadedFile $file): array
    {
        $svg = $this->safeSvg($file);
        $basePath = 'domains/'.$domain->id.'/favicons';
        $disk = Storage::disk('public');

        $assets = [
            'source' => "{$basePath}/source-logo.svg",
            'svg' => "{$basePath}/favicon.svg",
            'icon_16' => "{$basePath}/favicon-16x16.svg",
            'icon_32' => "{$basePath}/favicon-32x32.svg",
            'apple_touch_icon' => "{$basePath}/apple-touch-icon.svg",
            'mask_icon' => "{$basePath}/safari-pinned-tab.svg",
            'manifest' => "{$basePath}/site.webmanifest",
            'browserconfig' => "{$basePath}/browserconfig.xml",
        ];

        foreach (['source', 'svg', 'icon_16', 'icon_32', 'apple_touch_icon', 'mask_icon'] as $key) {
            $disk->put($assets[$key], $svg);
        }

        $this->writeManifest($domain, $assets, [
            [
                'src' => '/storage/'.$assets['svg'],
                'sizes' => 'any',
                'type' => 'image/svg+xml',
                'purpose' => 'any maskable',
            ],
        ]);
        $this->writeBrowserConfig($assets['browserconfig'], $assets['svg']);

        return $assets;
    }

    /**
     * @return array<string, string>
     */
    private function generateFromRaster(Domain $domain, UploadedFile $file): array
    {
        $image = $this->rasterImage($file);
        $basePath = 'domains/'.$domain->id.'/favicons';
        $disk = Storage::disk('public');
        $extension = $this->sourceExtension($file);

        $assets = [
            'source' => "{$basePath}/source-logo.{$extension}",
            'icon' => "{$basePath}/favicon.ico",
            'icon_16' => "{$basePath}/favicon-16x16.png",
            'icon_32' => "{$basePath}/favicon-32x32.png",
            'icon_48' => "{$basePath}/favicon-48x48.png",
            'apple_touch_icon' => "{$basePath}/apple-touch-icon.png",
            'android_192' => "{$basePath}/android-chrome-192x192.png",
            'android_512' => "{$basePath}/android-chrome-512x512.png",
            'mstile_150' => "{$basePath}/mstile-150x150.png",
            'manifest' => "{$basePath}/site.webmanifest",
            'browserconfig' => "{$basePath}/browserconfig.xml",
        ];

        $disk->putFileAs($basePath, $file, basename($assets['source']));

        $pngs = [
            'icon_16' => $this->pngBytes($image, 16),
            'icon_32' => $this->pngBytes($image, 32),
            'icon_48' => $this->pngBytes($image, 48),
            'apple_touch_icon' => $this->pngBytes($image, 180),
            'android_192' => $this->pngBytes($image, 192),
            'android_512' => $this->pngBytes($image, 512),
            'mstile_150' => $this->pngBytes($image, 150),
        ];

        foreach ($pngs as $key => $bytes) {
            $disk->put($assets[$key], $bytes);
        }

        $disk->put($assets['icon'], $this->icoBytes([
            16 => $pngs['icon_16'],
            32 => $pngs['icon_32'],
            48 => $pngs['icon_48'],
        ]));

        $this->writeManifest($domain, $assets, [
            [
                'src' => '/storage/'.$assets['android_192'],
                'sizes' => '192x192',
                'type' => 'image/png',
                'purpose' => 'any maskable',
            ],
            [
                'src' => '/storage/'.$assets['android_512'],
                'sizes' => '512x512',
                'type' => 'image/png',
                'purpose' => 'any maskable',
            ],
        ]);
        $this->writeBrowserConfig($assets['browserconfig'], $assets['mstile_150']);

        imagedestroy($image);

        return $assets;
    }

    private function safeSvg(UploadedFile $file): string
    {
        $contents = file_get_contents($file->getRealPath());

        if (! is_string($contents) || ! str_contains(strtolower($contents), '<svg')) {
            throw ValidationException::withMessages([
                'favicon_logo' => __('The favicon logo must be a valid SVG file.'),
            ]);
        }

        if (preg_match('/<(script|iframe|object|embed)\b/i', $contents) === 1
            || preg_match('/\s+on[a-z]+\s*=/i', $contents) === 1
            || preg_match('/javascript\s*:/i', $contents) === 1
            || preg_match('/<!ENTITY/i', $contents) === 1
        ) {
            throw ValidationException::withMessages([
                'favicon_logo' => __('The favicon logo SVG contains unsafe markup.'),
            ]);
        }

        return $contents;
    }

    private function isSvg(UploadedFile $file): bool
    {
        if (str_contains((string) $file->getMimeType(), 'svg')) {
            return true;
        }

        if (strtolower($file->getClientOriginalExtension()) === 'svg') {
            return true;
        }

        $contents = file_get_contents($file->getRealPath(), false, null, 0, 512);

        return is_string($contents) && str_contains(strtolower($contents), '<svg');
    }

    private function rasterImage(UploadedFile $file): GdImage
    {
        $contents = file_get_contents($file->getRealPath());
        $image = is_string($contents) ? imagecreatefromstring($contents) : false;

        if (! $image instanceof GdImage) {
            throw ValidationException::withMessages([
                'favicon_logo' => __('The favicon logo must be a valid image file.'),
            ]);
        }

        return $image;
    }

    private function pngBytes(GdImage $source, int $size): string
    {
        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);
        $scale = min($size / $sourceWidth, $size / $sourceHeight);
        $targetWidth = max(1, (int) round($sourceWidth * $scale));
        $targetHeight = max(1, (int) round($sourceHeight * $scale));
        $targetX = (int) floor(($size - $targetWidth) / 2);
        $targetY = (int) floor(($size - $targetHeight) / 2);

        $canvas = imagecreatetruecolor($size, $size);
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        imagefilledrectangle($canvas, 0, 0, $size, $size, imagecolorallocatealpha($canvas, 0, 0, 0, 127));

        imagecopyresampled(
            $canvas,
            $source,
            $targetX,
            $targetY,
            0,
            0,
            $targetWidth,
            $targetHeight,
            $sourceWidth,
            $sourceHeight,
        );

        ob_start();
        imagepng($canvas, null, 9);
        $bytes = ob_get_clean();
        imagedestroy($canvas);

        return is_string($bytes) ? $bytes : '';
    }

    /**
     * @param  array<int, string>  $pngs
     */
    private function icoBytes(array $pngs): string
    {
        $count = count($pngs);
        $directory = pack('vvv', 0, 1, $count);
        $entries = '';
        $payload = '';
        $offset = 6 + ($count * 16);

        foreach ($pngs as $size => $png) {
            $length = strlen($png);
            $entries .= pack('CCCCvvVV', $size >= 256 ? 0 : $size, $size >= 256 ? 0 : $size, 0, 0, 1, 32, $length, $offset);
            $payload .= $png;
            $offset += $length;
        }

        return $directory.$entries.$payload;
    }

    /**
     * @param  array<string, string>  $assets
     * @param  list<array<string, string>>  $icons
     */
    private function writeManifest(Domain $domain, array $assets, array $icons): void
    {
        Storage::disk('public')->put($assets['manifest'], json_encode([
            'name' => $domain->siteTitle(),
            'short_name' => $domain->company_name ?: $domain->siteTitle(),
            'icons' => $icons,
            'theme_color' => data_get($domain->effectiveTemplateSettings(), 'primary_color', '#0f6f7a'),
            'background_color' => data_get($domain->effectiveTemplateSettings(), 'surface_color', '#ffffff'),
            'display' => 'standalone',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    private function writeBrowserConfig(string $path, string $tilePath): void
    {
        Storage::disk('public')->put($path, <<<XML
<?xml version="1.0" encoding="utf-8"?>
<browserconfig>
  <msapplication>
    <tile>
      <square150x150logo src="/storage/{$tilePath}"/>
      <TileColor>#ffffff</TileColor>
    </tile>
  </msapplication>
</browserconfig>
XML);
    }

    private function sourceExtension(UploadedFile $file): string
    {
        return match ($file->getMimeType()) {
            'image/jpeg' => 'jpg',
            'image/webp' => 'webp',
            default => 'png',
        };
    }
}
