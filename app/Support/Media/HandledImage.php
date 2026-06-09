<?php

namespace App\Support\Media;

use Illuminate\Database\Eloquent\Model;

class HandledImage
{
    public function __construct(
        private readonly Model|string|null $source,
        private readonly ?string $fallbackName = null,
    ) {}

    public static function fromPath(?string $path, ?string $fallbackName = null): self
    {
        return new self($path, $fallbackName);
    }

    public function handle(
        ?int $width = null,
        ?int $height = null,
        bool $crop = false,
        ?string $format = null,
    ): ?string {
        return app(ImageVariantFactory::class)->url($this->source, $width, $height, $crop, $format, $this->fallbackName);
    }

    public function lightbox(?string $format = null): ?string
    {
        return $this->handle(
            (int) config('cms_images.lightbox.width', 1800),
            (int) config('cms_images.lightbox.height', 1800),
            (bool) config('cms_images.lightbox.crop', false),
            $format,
        );
    }

    public function alt(?string $fallback = null): string
    {
        if (! $this->source instanceof Model) {
            return (string) ($fallback ?? '');
        }

        if ((bool) $this->source->getAttribute('is_decorative')) {
            return '';
        }

        foreach (['alt_text', 'title_text', 'caption', 'name', 'title', 'original_filename'] as $field) {
            $value = $this->source->getAttribute($field);

            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return (string) ($fallback ?? '');
    }
}
