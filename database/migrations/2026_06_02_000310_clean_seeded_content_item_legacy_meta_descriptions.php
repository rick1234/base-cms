<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var array<string, string>
     */
    private array $descriptions = [
        'website-redesign-checklist' => 'Planning guide for CMS rebuilds, migrations, redirects, SEO metadata, accessibility, and ownership.',
        'launching-the-acme-demo-site' => 'Demo news item for seeded CMS module content, category relationships, and public metadata.',
        'how-we-model-catalog-content' => 'Implementation note about clear catalog category trees, product metadata, and useful navigation links.',
        'support-retainers-that-actually-help' => 'Support article about care models, releases, small improvements, monitoring, and content help.',
        'new-integration-playbook' => 'Integration update for forms, catalogs, CRM workflows, search, and admin editing.',
        'editor-training-day-announced' => 'Announcement for a hands-on CMS onboarding day for editors.',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('content_items') || ! Schema::hasColumn('content_items', 'meta_description')) {
            return;
        }

        foreach ($this->descriptions as $slug => $description) {
            DB::table('content_items')
                ->where('slug', $slug)
                ->update(['meta_description' => $description]);
        }
    }

    public function down(): void
    {
        //
    }
};
