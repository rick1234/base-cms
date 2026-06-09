<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('content_items') && Schema::hasTable('cms_modules')) {
            return;
        }

        $this->extendUsersTable();

        $this->createContentTables();
        $this->createBannerTables();
        $this->createFormTables();
        $this->createEventTables();
        $this->createFaqTables();
        $this->createDownloadTables();
        $this->createLocationTables();
        $this->createVacancyTables();
        $this->createCatalogTables();
        $this->createOrderTables();
        $this->createPlatformTables();
        $this->createLocalizationTables();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (array_reverse($this->tables()) as $table) {
            Schema::dropIfExists($table);
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn([
                'username',
                'locale',
                'active_from',
                'active_until',
                'is_active',
                'salutation',
                'first_name',
                'middle_name',
                'last_name',
                'gender',
                'street',
                'house_number',
                'house_number_addition',
                'postal_code',
                'city',
                'country_code',
                'phone',
                'company_name',
                'image_path',
                'role_id',
                'created_by',
                'updated_by',
                'legacy_id',
                'legacy_payload',
            ]);
        });
    }

    /**
     * @return list<string>
     */
    private function tables(): array
    {
        return [
            'content_categories',
            'content_items',
            'content_category_content_item',
            'content_images',
            'content_attachments',
            'content_block_layouts',
            'content_blocks',
            'content_block_part_containers',
            'content_block_parts',
            'banner_categories',
            'banners',
            'banner_category_banner',
            'banner_translations',
            'form_categories',
            'forms',
            'form_category_form',
            'form_blocks',
            'form_rows',
            'form_fields',
            'form_field_options',
            'form_messages',
            'form_message_fields',
            'form_submissions',
            'form_submission_answers',
            'event_categories',
            'events',
            'event_category_event',
            'event_images',
            'event_attachments',
            'event_parts',
            'faq_categories',
            'faq_items',
            'faq_category_faq_item',
            'faq_images',
            'faq_attachments',
            'faq_videos',
            'download_categories',
            'downloads',
            'download_category_download',
            'location_categories',
            'locations',
            'location_category_location',
            'location_images',
            'location_opening_hours',
            'location_special_opening_hours',
            'vacancy_categories',
            'vacancies',
            'vacancy_category_vacancy',
            'vacancy_attachments',
            'catalog_categories',
            'catalog_brands',
            'catalog_products',
            'catalog_category_product',
            'catalog_product_images',
            'catalog_product_attachments',
            'catalog_product_options',
            'catalog_product_option_values',
            'catalog_product_translations',
            'catalog_combination_set_products',
            'catalog_combination_sets',
            'catalog_product_videos',
            'redirects',
            'urls',
            'url_references',
            'domains',
            'user_categories',
            'user_logins',
            'user_category_user',
            'roles',
            'permissions',
            'role_permissions',
            'languages',
            'iso_languages',
            'countries',
            'country_codes',
            'translation_keys',
            'translation_values',
            'translations',
        ];
    }

    private function extendUsersTable(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('username')->nullable()->index();
            $table->string('locale', 8)->nullable()->index();
            $table->date('active_from')->nullable();
            $table->date('active_until')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->string('salutation')->nullable();
            $table->string('first_name')->nullable();
            $table->string('middle_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('gender', 16)->nullable();
            $table->string('street')->nullable();
            $table->string('house_number')->nullable();
            $table->string('house_number_addition')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('city')->nullable();
            $table->string('country_code', 8)->nullable();
            $table->string('phone')->nullable();
            $table->string('company_name')->nullable();
            $table->string('image_path')->nullable();
            $table->unsignedBigInteger('role_id')->nullable()->index();
            $table->foreignId('created_by')->nullable()->index();
            $table->foreignId('updated_by')->nullable()->index();
            $table->unsignedBigInteger('legacy_id')->nullable()->index();
            $table->json('legacy_payload')->nullable();
        });
    }

    private function createContentTables(): void
    {
        $this->createCategoryTable('content_categories');
        $this->createPublishableTable('content_items');
        $this->createPivotTable('content_category_content_item', 'content_category_id', 'content_item_id');
        $this->createMediaTable('content_images', 'content_item_id');
        $this->createAttachmentTable('content_attachments', 'content_item_id');

        Schema::create('content_block_layouts', function (Blueprint $table): void {
            $this->baseColumns($table);
            $table->string('name');
            $table->string('handle')->nullable()->index();
            $table->json('columns')->nullable();
            $this->timestamps($table);
        });

        Schema::create('content_blocks', function (Blueprint $table): void {
            $this->baseColumns($table);
            $table->unsignedBigInteger('content_item_id')->nullable()->index();
            $table->unsignedBigInteger('layout_id')->nullable()->index();
            $table->string('name')->nullable();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->json('configuration')->nullable();
            $this->timestamps($table);
        });

        Schema::create('content_block_part_containers', function (Blueprint $table): void {
            $this->baseColumns($table);
            $table->unsignedBigInteger('block_id')->nullable()->index();
            $table->string('region')->nullable()->index();
            $table->unsignedInteger('sort_order')->default(0);
            $this->timestamps($table);
        });

        Schema::create('content_block_parts', function (Blueprint $table): void {
            $this->baseColumns($table);
            $table->unsignedBigInteger('container_id')->nullable()->index();
            $table->string('type')->nullable()->index();
            $table->string('title')->nullable();
            $table->longText('content')->nullable();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->json('settings')->nullable();
            $this->timestamps($table);
        });
    }

    private function createBannerTables(): void
    {
        $this->createCategoryTable('banner_categories');
        $this->createSimpleMediaEntityTable('banners');
        $this->createPivotTable('banner_category_banner', 'banner_category_id', 'banner_id');
        $this->createTranslationTable('banner_translations', 'banner_id');
    }

    private function createFormTables(): void
    {
        $this->createCategoryTable('form_categories');

        Schema::create('forms', function (Blueprint $table): void {
            $this->baseColumns($table);
            $table->string('name');
            $table->string('slug')->nullable()->index();
            $table->string('locale', 8)->nullable()->index();
            $table->text('description')->nullable();
            $table->text('submit_text')->nullable();
            $table->text('success_message')->nullable();
            $table->string('recipient_email')->nullable();
            $table->string('status')->default('draft')->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('settings')->nullable();
            $this->timestamps($table);
            $table->softDeletes();
        });

        $this->createPivotTable('form_category_form', 'form_category_id', 'form_id');

        Schema::create('form_blocks', function (Blueprint $table): void {
            $this->baseColumns($table);
            $table->unsignedBigInteger('form_id')->index();
            $table->string('title')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('settings')->nullable();
            $this->timestamps($table);
        });

        Schema::create('form_rows', function (Blueprint $table): void {
            $this->baseColumns($table);
            $table->unsignedBigInteger('block_id')->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('settings')->nullable();
            $this->timestamps($table);
        });

        Schema::create('form_fields', function (Blueprint $table): void {
            $this->baseColumns($table);
            $table->unsignedBigInteger('row_id')->index();
            $table->string('name')->index();
            $table->string('label')->nullable();
            $table->string('type')->index();
            $table->text('help_text')->nullable();
            $table->boolean('is_required')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('validation_rules')->nullable();
            $table->json('settings')->nullable();
            $this->timestamps($table);
        });

        Schema::create('form_field_options', function (Blueprint $table): void {
            $this->baseColumns($table);
            $table->unsignedBigInteger('field_id')->index();
            $table->string('label');
            $table->string('value')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $this->timestamps($table);
        });

        Schema::create('form_messages', function (Blueprint $table): void {
            $this->baseColumns($table);
            $table->unsignedBigInteger('form_id')->index();
            $table->string('subject')->nullable();
            $table->longText('body')->nullable();
            $table->string('type')->default('notification')->index();
            $this->timestamps($table);
        });

        Schema::create('form_message_fields', function (Blueprint $table): void {
            $this->baseColumns($table);
            $table->unsignedBigInteger('message_id')->index();
            $table->unsignedBigInteger('field_id')->index();
            $table->string('placeholder')->nullable();
            $this->timestamps($table);
        });

        Schema::create('form_submissions', function (Blueprint $table): void {
            $this->baseColumns($table);
            $table->unsignedBigInteger('form_id')->index();
            $table->string('locale', 8)->nullable();
            $table->string('remote_ip')->nullable();
            $table->string('user_agent')->nullable();
            $table->json('payload')->nullable();
            $this->timestamps($table);
        });

        Schema::create('form_submission_answers', function (Blueprint $table): void {
            $this->baseColumns($table);
            $table->unsignedBigInteger('submission_id')->index();
            $table->unsignedBigInteger('field_id')->nullable()->index();
            $table->string('field_name')->nullable()->index();
            $table->longText('value')->nullable();
            $this->timestamps($table);
        });
    }

    private function createEventTables(): void
    {
        $this->createCategoryTable('event_categories');
        $this->createPublishableTable('events');
        $this->createPivotTable('event_category_event', 'event_category_id', 'event_id');
        $this->createMediaTable('event_images', 'event_id');
        $this->createAttachmentTable('event_attachments', 'event_id');

        Schema::create('event_parts', function (Blueprint $table): void {
            $this->baseColumns($table);
            $table->unsignedBigInteger('event_id')->index();
            $table->string('title')->nullable();
            $table->longText('content')->nullable();
            $table->dateTime('starts_at')->nullable()->index();
            $table->dateTime('ends_at')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $this->timestamps($table);
        });
    }

    private function createFaqTables(): void
    {
        $this->createCategoryTable('faq_categories');
        $this->createPublishableTable('faq_items', 'question');
        $this->createPivotTable('faq_category_faq_item', 'faq_category_id', 'faq_item_id');
        $this->createMediaTable('faq_images', 'faq_item_id');
        $this->createAttachmentTable('faq_attachments', 'faq_item_id');
        $this->createVideoTable('faq_videos', 'faq_item_id');
    }

    private function createDownloadTables(): void
    {
        $this->createCategoryTable('download_categories');

        Schema::create('downloads', function (Blueprint $table): void {
            $this->baseColumns($table);
            $table->string('name');
            $table->string('type')->nullable()->index();
            $table->string('url');
            $table->string('status')->default('active')->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('metadata')->nullable();
            $this->timestamps($table);
            $table->softDeletes();
        });

        $this->createPivotTable('download_category_download', 'download_category_id', 'download_id');
    }

    private function createLocationTables(): void
    {
        $this->createCategoryTable('location_categories');

        Schema::create('locations', function (Blueprint $table): void {
            $this->baseColumns($table);
            $table->string('name')->nullable()->index();
            $table->string('street_address')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('city')->nullable();
            $table->string('country_code', 8)->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('website_url')->nullable();
            $table->string('chamber_of_commerce_number')->nullable();
            $table->longText('description')->nullable();
            $table->string('latitude')->nullable();
            $table->string('longitude')->nullable();
            $table->text('map_info')->nullable();
            $table->string('status')->default('active')->index();
            $table->date('active_from')->nullable();
            $table->date('active_until')->nullable();
            $table->json('metadata')->nullable();
            $this->timestamps($table);
            $table->softDeletes();
        });

        $this->createPivotTable('location_category_location', 'location_category_id', 'location_id');
        $this->createMediaTable('location_images', 'location_id');

        Schema::create('location_opening_hours', function (Blueprint $table): void {
            $this->baseColumns($table);
            $table->unsignedBigInteger('location_id')->index();
            $table->string('day')->index();
            $table->time('opens_at')->nullable();
            $table->time('closes_at')->nullable();
            $table->boolean('is_closed')->default(false);
            $this->timestamps($table);
        });

        Schema::create('location_special_opening_hours', function (Blueprint $table): void {
            $this->baseColumns($table);
            $table->unsignedBigInteger('location_id')->index();
            $table->string('title')->nullable();
            $table->date('date')->index();
            $table->time('opens_at')->nullable();
            $table->time('closes_at')->nullable();
            $table->boolean('is_closed')->default(false);
            $this->timestamps($table);
        });
    }

    private function createVacancyTables(): void
    {
        $this->createCategoryTable('vacancy_categories');
        $this->createPublishableTable('vacancies');
        $this->createPivotTable('vacancy_category_vacancy', 'vacancy_category_id', 'vacancy_id');
        $this->createAttachmentTable('vacancy_attachments', 'vacancy_id');
    }

    private function createCatalogTables(): void
    {
        $this->createCategoryTable('catalog_categories');
        $this->createSimpleNamedTable('catalog_brands');

        Schema::table('catalog_brands', function (Blueprint $table): void {
            $table->string('website_url')->nullable()->after('description');
            $table->string('logo_path')->nullable()->after('website_url');
            $table->text('intro')->nullable()->after('logo_path');
            $table->longText('body')->nullable()->after('intro');
            $table->string('meta_title')->nullable()->after('body');
            $table->text('meta_description')->nullable()->after('meta_title');
        });

        Schema::create('catalog_products', function (Blueprint $table): void {
            $this->baseColumns($table);
            $table->string('sku')->nullable()->unique();
            $table->string('name')->index();
            $table->longText('description')->nullable();
            $table->integer('price')->default(0);
            $table->text('meta_description')->nullable();
            $table->unsignedBigInteger('brand_id')->nullable()->index();
            $table->string('status')->default('draft')->index();
            $table->date('active_from')->nullable();
            $table->date('active_until')->nullable();
            $table->json('metadata')->nullable();
            $this->timestamps($table);
            $table->softDeletes();
        });

        $this->createPivotTable('catalog_category_product', 'catalog_category_id', 'catalog_product_id');
        $this->createMediaTable('catalog_product_images', 'catalog_product_id');
        $this->createAttachmentTable('catalog_product_attachments', 'catalog_product_id');

        Schema::create('catalog_product_options', function (Blueprint $table): void {
            $this->baseColumns($table);
            $table->unsignedBigInteger('catalog_product_id')->index();
            $table->string('label');
            $table->json('label_translations')->nullable();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $this->timestamps($table);
            $table->softDeletes();
        });

        Schema::create('catalog_product_option_values', function (Blueprint $table): void {
            $this->baseColumns($table);
            $table->unsignedBigInteger('catalog_product_option_id')->index();
            $table->string('value')->nullable();
            $table->json('value_translations')->nullable();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $this->timestamps($table);
            $table->softDeletes();
        });

        $this->createTranslationTable('catalog_product_translations', 'catalog_product_id');
        $this->createVideoTable('catalog_product_videos', 'catalog_product_id');

        Schema::create('catalog_combination_sets', function (Blueprint $table): void {
            $this->baseColumns($table);
            $table->string('name');
            $table->string('slug')->nullable()->index();
            $table->text('description')->nullable();
            $table->string('status')->default('active')->index();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $this->timestamps($table);
            $table->softDeletes();
        });

        Schema::create('catalog_combination_set_products', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('catalog_combination_set_id');
            $table->unsignedBigInteger('catalog_product_id');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index('catalog_combination_set_id', 'catalog_set_products_set_idx');
            $table->index('catalog_product_id', 'catalog_set_products_product_idx');
            $table->unique(['catalog_combination_set_id', 'catalog_product_id'], 'catalog_set_product_unique');
        });
    }

    private function createOrderTables(): void {}

    private function createPlatformTables(): void
    {
        Schema::create('redirects', function (Blueprint $table): void {
            $this->baseColumns($table);
            $table->string('source_path')->unique();
            $table->string('target_url');
            $table->unsignedSmallInteger('status_code')->default(301);
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('preserve_query')->default(false);
            $this->timestamps($table);
        });

        Schema::create('urls', function (Blueprint $table): void {
            $this->baseColumns($table);
            $table->string('path')->unique();
            $table->string('target_type')->nullable()->index();
            $table->unsignedBigInteger('target_id')->nullable()->index();
            $table->string('locale', 8)->nullable()->index();
            $table->boolean('is_restricted')->default(false);
            $table->json('metadata')->nullable();
            $this->timestamps($table);
        });

        Schema::create('url_references', function (Blueprint $table): void {
            $this->baseColumns($table);
            $table->string('source_path')->index();
            $table->string('target_path')->nullable();
            $table->string('target_type')->nullable()->index();
            $table->unsignedBigInteger('target_id')->nullable()->index();
            $table->boolean('is_active')->default(true)->index();
            $table->json('metadata')->nullable();
            $this->timestamps($table);
            $table->softDeletes();
        });

        Schema::create('domains', function (Blueprint $table): void {
            $this->baseColumns($table);
            $table->string('host')->unique();
            $table->string('name')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->json('settings')->nullable();
            $this->timestamps($table);
        });

        $this->createCategoryTable('user_categories');
        Schema::create('user_logins', function (Blueprint $table): void {
            $this->baseColumns($table);
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->date('login_date')->nullable();
            $table->time('login_time')->nullable();
            $table->string('remote_ip')->nullable();
            $table->string('host')->nullable();
            $this->timestamps($table);
        });

        $this->createSimpleRelationshipTable('user_category_user', 'user_id', 'user_category_id');
        $this->createSimpleNamedTable('roles');
        $this->createSimpleNamedTable('permissions');
        $this->createSimpleRelationshipTable('role_permissions', 'role_id', 'permission_id');

    }

    private function createLocalizationTables(): void
    {
        $this->createSimpleNamedTable('languages');
        $this->createSimpleNamedTable('iso_languages');
        $this->createSimpleNamedTable('countries');

        Schema::create('country_codes', function (Blueprint $table): void {
            $this->baseColumns($table);
            $table->string('code')->index();
            $table->string('country_code')->nullable()->index();
            $table->string('name')->nullable();
            $table->json('metadata')->nullable();
            $this->timestamps($table);
        });

        Schema::create('translation_keys', function (Blueprint $table): void {
            $this->baseColumns($table);
            $table->string('key')->unique();
            $table->string('group')->nullable()->index();
            $table->text('source_text')->nullable();
            $this->timestamps($table);
        });

        Schema::create('translation_values', function (Blueprint $table): void {
            $this->baseColumns($table);
            $table->unsignedBigInteger('translation_key_id')->index();
            $table->string('locale', 8)->index();
            $table->longText('value')->nullable();
            $this->timestamps($table);
        });

        Schema::create('translations', function (Blueprint $table): void {
            $this->baseColumns($table);
            $table->string('locale', 8)->index();
            $table->string('key')->index();
            $table->longText('value')->nullable();
            $table->json('metadata')->nullable();
            $this->timestamps($table);
        });
    }

    private function createPublishableTable(string $tableName, string $labelColumn = 'title'): void
    {
        Schema::create($tableName, function (Blueprint $table) use ($labelColumn): void {
            $this->baseColumns($table);
            $table->string($labelColumn)->index();
            $table->string('slug')->nullable()->index();
            $table->string('locale', 8)->nullable()->index();
            $table->text('intro')->nullable();
            $table->longText('body')->nullable();
            $table->string('image_path')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('status')->default('draft')->index();
            $table->date('active_from')->nullable();
            $table->date('active_until')->nullable();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->json('metadata')->nullable();
            $this->timestamps($table);
            $table->softDeletes();
        });
    }

    private function createSimpleMediaEntityTable(string $tableName): void
    {
        Schema::create($tableName, function (Blueprint $table): void {
            $this->baseColumns($table);
            $table->string('title')->nullable()->index();
            $table->string('image_path')->nullable();
            $table->string('link_url')->nullable();
            $table->string('button_text')->nullable();
            $table->text('text')->nullable();
            $table->string('status')->default('draft')->index();
            $table->date('starts_at')->nullable();
            $table->date('ends_at')->nullable();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->json('metadata')->nullable();
            $this->timestamps($table);
            $table->softDeletes();
        });
    }

    private function createSimpleNamedTable(string $tableName): void
    {
        Schema::create($tableName, function (Blueprint $table): void {
            $this->baseColumns($table);
            $table->string('name')->index();
            $table->string('slug')->nullable()->index();
            $table->text('description')->nullable();
            $table->string('status')->default('active')->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('metadata')->nullable();
            $this->timestamps($table);
            $table->softDeletes();
        });
    }

    private function createCategoryTable(string $tableName): void
    {
        Schema::create($tableName, function (Blueprint $table): void {
            $this->baseColumns($table);
            $table->unsignedBigInteger('parent_id')->nullable()->index();
            $table->string('name')->index();
            $table->string('slug')->nullable()->index();
            $table->text('description')->nullable();
            $table->string('image_path')->nullable();
            $table->string('status')->default('active')->index();
            $table->boolean('is_hidden_from_navigation')->default(false);
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->json('metadata')->nullable();
            $this->timestamps($table);
            $table->softDeletes();
        });
    }

    private function createMediaTable(string $tableName, string $ownerColumn): void
    {
        Schema::create($tableName, function (Blueprint $table) use ($ownerColumn): void {
            $this->baseColumns($table);
            $table->unsignedBigInteger($ownerColumn)->index();
            $table->string('folder')->nullable();
            $table->string('image_path')->nullable();
            $table->string('caption')->nullable();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $this->timestamps($table);
            $table->softDeletes();
        });
    }

    private function createAttachmentTable(string $tableName, string $ownerColumn): void
    {
        Schema::create($tableName, function (Blueprint $table) use ($ownerColumn): void {
            $this->baseColumns($table);
            $table->unsignedBigInteger($ownerColumn)->index();
            $table->string('name')->nullable();
            $table->string('type')->nullable()->index();
            $table->string('url');
            $table->unsignedInteger('sort_order')->default(0)->index();
            $this->timestamps($table);
            $table->softDeletes();
        });
    }

    private function createVideoTable(string $tableName, string $ownerColumn): void
    {
        Schema::create($tableName, function (Blueprint $table) use ($ownerColumn): void {
            $this->baseColumns($table);
            $table->unsignedBigInteger($ownerColumn)->index();
            $table->string('title')->nullable();
            $table->string('url');
            $table->string('provider')->nullable();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $this->timestamps($table);
        });
    }

    private function createTranslationTable(string $tableName, string $ownerColumn): void
    {
        Schema::create($tableName, function (Blueprint $table) use ($ownerColumn): void {
            $this->baseColumns($table);
            $table->unsignedBigInteger($ownerColumn)->index();
            $table->string('locale', 8)->index();
            $table->string('title')->nullable();
            $table->string('subtitle')->nullable();
            $table->string('link_url')->nullable();
            $table->string('button_text')->nullable();
            $table->longText('content')->nullable();
            $table->json('metadata')->nullable();
            $this->timestamps($table);
        });
    }

    private function createPivotTable(string $tableName, string $leftColumn, string $rightColumn): void
    {
        Schema::create($tableName, function (Blueprint $table) use ($leftColumn, $rightColumn, $tableName): void {
            $this->baseColumns($table);
            $table->unsignedBigInteger($leftColumn);
            $table->unsignedBigInteger($rightColumn);
            $table->unsignedInteger('sort_order')->default(0)->index();
            $this->timestamps($table);
            $table->index($leftColumn, $this->indexName($tableName, $leftColumn));
            $table->index($rightColumn, $this->indexName($tableName, $rightColumn));
            $table->unique([$leftColumn, $rightColumn], $tableName.'_unique');
        });
    }

    private function createSimpleRelationshipTable(string $tableName, string $leftColumn, string $rightColumn): void
    {
        Schema::create($tableName, function (Blueprint $table) use ($leftColumn, $rightColumn, $tableName): void {
            $this->baseColumns($table);
            $table->unsignedBigInteger($leftColumn);
            $table->unsignedBigInteger($rightColumn);
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->json('metadata')->nullable();
            $this->timestamps($table);
            $table->index($leftColumn, $this->indexName($tableName, $leftColumn));
            $table->index($rightColumn, $this->indexName($tableName, $rightColumn));
        });
    }

    private function indexName(string $tableName, string $columnName): string
    {
        $name = $tableName.'_'.$columnName.'_index';

        if (strlen($name) <= 64) {
            return $name;
        }

        return substr($tableName, 0, 32).'_'
            .substr($columnName, 0, 16).'_'
            .substr(md5($name), 0, 8)
            .'_index';
    }

    private function baseColumns(Blueprint $table): void
    {
        $table->id();
        $table->uuid('uuid')->nullable()->unique();
        $table->unsignedBigInteger('legacy_id')->nullable()->index();
    }

    private function timestamps(Blueprint $table): void
    {
        $table->foreignId('created_by')->nullable()->index();
        $table->foreignId('updated_by')->nullable()->index();
        $table->timestamps();
    }
};
