# Content Block Editor

The content edit page uses a Filament Builder based block editor embedded in `admin/content/{id}/edit`.

Structured blocks are stored on `content_items.structured_blocks` as JSON. Each item uses this shape:

```json
{
  "type": "text",
  "uuid": "generated-uuid",
  "layout": "full",
  "data": {},
  "settings": {}
}
```

The old legacy block system is not used by the edit form anymore. These legacy tables are kept as migration source data:

- `content_block_layouts`
- `content_blocks`
- `content_block_part_containers`
- `content_block_parts`

Before writing converted blocks, the migration command stores the old structure in `content_items.legacy_block_snapshot` and sets `legacy_blocks_migrated_at`.

Convert existing legacy block rows with:

```bash
php artisan cms:migrate-content-blocks --dry-run
php artisan cms:migrate-content-blocks
```

Use `--force` only when an existing `structured_blocks` value should be replaced.

## Adding A Block

Create a class that implements `App\Cms\PageBlocks\Contracts\PageBlock`. The simplest path is to extend `App\Cms\PageBlocks\Blocks\BasePageBlock`.

Register it from a service provider:

```php
use App\Cms\PageBlocks\PageBlockRegistry;

public function boot(): void
{
    app(PageBlockRegistry::class)->register(MyCustomBlock::class);
}
```

Each block defines its Filament schema, admin preview view, frontend Blade view, default data, and default settings. Future modules should register their own blocks from their own provider instead of editing the content editor component directly.

Frontend rendering lives in `resources/views/components/page-blocks`. Rich text output is sanitized before rendering, normal text is escaped by Blade, URLs are restricted to safe schemes, and uploads are rendered through the public storage disk.
