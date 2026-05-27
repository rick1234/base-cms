<?php

namespace App\Console\Commands;

use App\Cms\PageBlocks\LegacyContentBlockConverter;
use App\Models\Cms\ContentItem;
use Illuminate\Console\Command;

class MigrateContentBlocksToStructuredBlocks extends Command
{
    protected $signature = 'cms:migrate-content-blocks {--dry-run : Show what would be migrated without writing changes} {--force : Replace existing structured blocks}';

    protected $description = 'Convert legacy content block rows to structured JSON blocks on content_items.';

    public function handle(LegacyContentBlockConverter $converter): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');
        $migrated = 0;

        ContentItem::query()
            ->with('blocks.layout', 'blocks.containers.parts')
            ->whereHas('blocks')
            ->orderBy('id')
            ->chunkById(50, function ($items) use ($converter, $dryRun, $force, &$migrated): void {
                foreach ($items as $contentItem) {
                    if (! $force && filled($contentItem->structured_blocks)) {
                        $this->line("Skipping content item {$contentItem->id}; structured blocks already exist.");

                        continue;
                    }

                    $blocks = $converter->convert($contentItem);

                    if ($blocks === []) {
                        $this->line("Skipping content item {$contentItem->id}; no convertible legacy blocks.");

                        continue;
                    }

                    $migrated++;
                    $this->line(($dryRun ? 'Would migrate' : 'Migrating')." content item {$contentItem->id} with ".count($blocks).' blocks.');

                    if ($dryRun) {
                        continue;
                    }

                    $contentItem->forceFill([
                        'structured_blocks' => $blocks,
                        'legacy_block_snapshot' => $converter->snapshot($contentItem),
                        'legacy_blocks_migrated_at' => now(),
                    ])->save();
                }
            });

        $this->info(($dryRun ? 'Dry run complete.' : 'Migration complete.').' '.$migrated.' content items processed.');

        return self::SUCCESS;
    }
}
