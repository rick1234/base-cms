<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var array<string, string>
     */
    private array $descriptionReplacements = [
        '%CMS replacement%' => 'Planning guide for CMS rebuilds, migrations, redirects, SEO metadata, accessibility, and ownership.',
        '%CMS modules%' => 'Demo news item for seeded CMS module content, category relationships, and public metadata.',
        '%category trees%' => 'Implementation note about clear catalog category trees, product metadata, and useful navigation links.',
        '%content help%' => 'Support article about care models, releases, small improvements, monitoring, and content help.',
        '%CRM workflows%' => 'Integration update for forms, catalogs, CRM workflows, search, and admin editing.',
        '%CMS workflow%' => 'Announcement for a hands-on CMS onboarding day for editors.',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('content_items') || ! Schema::hasColumn('content_items', 'meta_description')) {
            return;
        }

        foreach ($this->descriptionReplacements as $pattern => $replacement) {
            DB::table('content_items')
                ->where('meta_description', 'like', $pattern)
                ->update(['meta_description' => $replacement]);
        }
    }

    public function down(): void
    {
        //
    }
};
