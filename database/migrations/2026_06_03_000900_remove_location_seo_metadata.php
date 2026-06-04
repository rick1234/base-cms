<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('locations')) {
            return;
        }

        if (Schema::hasColumn('locations', 'metadata')) {
            DB::table('locations')
                ->whereNotNull('metadata')
                ->orderBy('id')
                ->get(['id', 'metadata'])
                ->each(function (object $location): void {
                    $metadata = json_decode((string) $location->metadata, true);

                    if (! is_array($metadata)) {
                        return;
                    }

                    $original = $metadata;
                    unset($metadata['slug'], $metadata['seo_title'], $metadata['meta_description']);

                    if ($metadata === $original) {
                        return;
                    }

                    DB::table('locations')
                        ->where('id', $location->id)
                        ->update([
                            'metadata' => $metadata === [] ? null : json_encode($metadata),
                        ]);
                });
        }

        Schema::table('locations', function (Blueprint $table): void {
            foreach (['meta_title', 'og_title', 'og_description', 'og_image', 'canonical_url', 'robots'] as $column) {
                if (Schema::hasColumn('locations', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    public function down(): void
    {
        //
    }
};
