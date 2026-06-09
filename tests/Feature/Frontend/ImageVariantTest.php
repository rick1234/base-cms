<?php

namespace Tests\Feature\Frontend;

use App\Models\Cms\ContentImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageVariantTest extends TestCase
{
    use RefreshDatabase;

    public function test_image_handle_generates_cached_seo_named_webp_variant(): void
    {
        Storage::fake('public');
        $this->makeImage('content/original-file-name.jpg', 640, 360);

        $image = ContentImage::query()->create([
            'content_item_id' => 1,
            'image_path' => 'content/original-file-name.jpg',
            'alt_text' => 'Fast SEO Friendly Image',
        ]);

        $url = $image->image->handle(200, 200, true, 'webp');

        $this->assertIsString($url);
        $this->assertStringContainsString('/storage/image-cache/', $url);
        $this->assertStringContainsString('fast-seo-friendly-image-200x200-crop.webp', $url);

        $variantPath = $this->storagePathFromUrl($url);
        Storage::disk('public')->assertExists($variantPath);

        [$width, $height, $type] = getimagesize(Storage::disk('public')->path($variantPath));

        $this->assertSame(200, $width);
        $this->assertSame(200, $height);
        $this->assertSame(IMAGETYPE_WEBP, $type);
    }

    public function test_default_frontend_format_can_be_overridden_per_handle(): void
    {
        Storage::fake('public');
        Config::set('cms_images.default_format', 'webp');
        $this->makeImage('content/source.jpg', 640, 360);

        $image = ContentImage::query()->create([
            'content_item_id' => 1,
            'image_path' => 'content/source.jpg',
            'caption' => 'Override Format',
        ]);

        $defaultUrl = $image->image->handle(320, null);
        $jpgUrl = $image->image->handle(320, null, false, 'jpg');

        $this->assertStringEndsWith('.webp', parse_url((string) $defaultUrl, PHP_URL_PATH));
        $this->assertStringEndsWith('.jpg', parse_url((string) $jpgUrl, PHP_URL_PATH));
        Storage::disk('public')->assertExists($this->storagePathFromUrl((string) $defaultUrl));
        Storage::disk('public')->assertExists($this->storagePathFromUrl((string) $jpgUrl));
    }

    public function test_media_image_component_outputs_fancybox_link_and_dimensions(): void
    {
        Storage::fake('public');
        $this->makeImage('content/component-source.jpg', 1200, 800);

        $image = ContentImage::query()->create([
            'content_item_id' => 1,
            'image_path' => 'content/component-source.jpg',
            'alt_text' => 'Component SEO Image',
        ]);

        $html = Blade::render(
            '<x-media.image :image="$image" :width="300" :height="200" crop group="test-gallery" />',
            ['image' => $image],
        );

        $this->assertStringContainsString('data-fancybox="test-gallery"', $html);
        $this->assertStringContainsString('component-seo-image-300x200-crop.webp', $html);
        $this->assertStringContainsString('width="300"', $html);
        $this->assertStringContainsString('height="200"', $html);
        $this->assertStringContainsString('alt="Component SEO Image"', $html);
    }

    private function makeImage(string $path, int $width, int $height): void
    {
        $absolutePath = Storage::disk('public')->path($path);
        $directory = dirname($absolutePath);

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $image = imagecreatetruecolor($width, $height);
        $color = imagecolorallocate($image, 30, 120, 180);
        imagefilledrectangle($image, 0, 0, $width, $height, $color);
        imagejpeg($image, $absolutePath, 90);
        imagedestroy($image);
    }

    private function storagePathFromUrl(string $url): string
    {
        return ltrim((string) str(parse_url($url, PHP_URL_PATH) ?: '')->replaceStart('/storage/', ''), '/');
    }
}
