<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('website_templates', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->nullable()->unique();
            $table->string('handle')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('stylesheet_path')->nullable();
            $table->string('asset_path')->nullable();
            $table->string('view_path')->nullable();
            $table->json('default_settings')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->foreignId('created_by')->nullable()->index();
            $table->foreignId('updated_by')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('domains', function (Blueprint $table): void {
            if (! Schema::hasColumn('domains', 'website_template_id')) {
                $table->foreignId('website_template_id')
                    ->nullable()
                    ->after('name')
                    ->constrained('website_templates')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('domains', 'company_name')) {
                $table->string('company_name')->nullable()->after('name');
            }

            if (! Schema::hasColumn('domains', 'default_locale')) {
                $table->string('default_locale', 16)->nullable()->after('company_name')->index();
            }

            if (! Schema::hasColumn('domains', 'title_separator')) {
                $table->string('title_separator', 16)->default('|')->after('default_locale');
            }

            if (! Schema::hasColumn('domains', 'canonical_base_url')) {
                $table->string('canonical_base_url')->nullable()->after('title_separator');
            }

            if (! Schema::hasColumn('domains', 'default_meta_title')) {
                $table->string('default_meta_title')->nullable()->after('canonical_base_url');
            }

            if (! Schema::hasColumn('domains', 'default_meta_description')) {
                $table->text('default_meta_description')->nullable()->after('default_meta_title');
            }

            if (! Schema::hasColumn('domains', 'default_og_title')) {
                $table->string('default_og_title')->nullable()->after('default_meta_description');
            }

            if (! Schema::hasColumn('domains', 'default_og_description')) {
                $table->text('default_og_description')->nullable()->after('default_og_title');
            }

            if (! Schema::hasColumn('domains', 'default_og_image')) {
                $table->string('default_og_image')->nullable()->after('default_og_description');
            }

            if (! Schema::hasColumn('domains', 'robots')) {
                $table->string('robots')->nullable()->after('default_og_image');
            }

            if (! Schema::hasColumn('domains', 'active_frontend_locales')) {
                $table->json('active_frontend_locales')->nullable()->after('robots');
            }

            if (! Schema::hasColumn('domains', 'active_backend_locales')) {
                $table->json('active_backend_locales')->nullable()->after('active_frontend_locales');
            }

            if (! Schema::hasColumn('domains', 'template_settings')) {
                $table->json('template_settings')->nullable()->after('settings');
            }

            if (! Schema::hasColumn('domains', 'social_links')) {
                $table->json('social_links')->nullable()->after('template_settings');
            }

            if (! Schema::hasColumn('domains', 'contact_form_id')) {
                $table->foreignId('contact_form_id')->nullable()->after('social_links')->index();
            }

            if (! Schema::hasColumn('domains', 'public_integrations')) {
                $table->json('public_integrations')->nullable()->after('contact_form_id');
            }

            if (! Schema::hasColumn('domains', 'integration_credentials')) {
                $table->text('integration_credentials')->nullable()->after('public_integrations');
            }

            if (! Schema::hasColumn('domains', 'favicon_svg_path')) {
                $table->string('favicon_svg_path')->nullable()->after('integration_credentials');
            }

            if (! Schema::hasColumn('domains', 'favicon_assets')) {
                $table->json('favicon_assets')->nullable()->after('favicon_svg_path');
            }

            if (! Schema::hasColumn('domains', 'is_development')) {
                $table->boolean('is_development')->default(false)->after('is_active')->index();
            }

            if (! Schema::hasColumn('domains', 'sort_order')) {
                $table->unsignedInteger('sort_order')->default(0)->after('is_development')->index();
            }

            if (! Schema::hasColumn('domains', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        Schema::create('domain_aliases', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->nullable()->unique();
            $table->foreignId('domain_id')->constrained('domains')->cascadeOnDelete();
            $table->string('host')->unique();
            $table->boolean('is_primary')->default(false)->index();
            $table->foreignId('created_by')->nullable()->index();
            $table->foreignId('updated_by')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('domain_aliases');

        Schema::table('domains', function (Blueprint $table): void {
            if (Schema::hasColumn('domains', 'website_template_id')) {
                $table->dropConstrainedForeignId('website_template_id');
            }

            if (Schema::hasColumn('domains', 'contact_form_id')) {
                $table->dropColumn('contact_form_id');
            }

            $columns = [
                'company_name',
                'default_locale',
                'title_separator',
                'canonical_base_url',
                'default_meta_title',
                'default_meta_description',
                'default_og_title',
                'default_og_description',
                'default_og_image',
                'robots',
                'active_frontend_locales',
                'active_backend_locales',
                'template_settings',
                'social_links',
                'public_integrations',
                'integration_credentials',
                'favicon_svg_path',
                'favicon_assets',
                'is_development',
                'sort_order',
                'deleted_at',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('domains', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::dropIfExists('website_templates');
    }
};
