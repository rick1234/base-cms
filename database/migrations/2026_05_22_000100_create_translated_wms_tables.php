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
        $this->extendUsersTable();

        $this->createContentTables();
        $this->createBannerTables();
        $this->createSliderTables();
        $this->createFormTables();
        $this->createEventTables();
        $this->createFaqTables();
        $this->createDownloadTables();
        $this->createLocationTables();
        $this->createVacancyTables();
        $this->createCatalogTables();
        $this->createOrderTables();
        $this->createMailingTables();
        $this->createNewsletterTables();
        $this->createRadioTables();
        $this->createPlatformTables();
        $this->createLocalizationTables();
        $this->createUtilityTables();
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
            'wms_content_categories',
            'wms_content_items',
            'wms_content_category_content_item',
            'wms_content_images',
            'wms_content_attachments',
            'wms_content_block_layouts',
            'wms_content_blocks',
            'wms_content_block_part_containers',
            'wms_content_block_parts',
            'wms_banner_categories',
            'wms_banners',
            'wms_banner_category_banner',
            'wms_banner_translations',
            'wms_slider_categories',
            'wms_sliders',
            'wms_slider_category_slider',
            'wms_form_categories',
            'wms_forms',
            'wms_form_category_form',
            'wms_form_blocks',
            'wms_form_rows',
            'wms_form_fields',
            'wms_form_field_options',
            'wms_form_messages',
            'wms_form_message_fields',
            'wms_form_submissions',
            'wms_form_submission_answers',
            'wms_event_categories',
            'wms_events',
            'wms_event_category_event',
            'wms_event_images',
            'wms_event_attachments',
            'wms_event_parts',
            'wms_faq_categories',
            'wms_faq_items',
            'wms_faq_category_faq_item',
            'wms_faq_images',
            'wms_faq_attachments',
            'wms_faq_videos',
            'wms_download_categories',
            'wms_downloads',
            'wms_download_category_download',
            'wms_location_categories',
            'wms_locations',
            'wms_location_category_location',
            'wms_location_images',
            'wms_location_opening_hours',
            'wms_location_special_opening_hours',
            'wms_vacancy_categories',
            'wms_vacancies',
            'wms_vacancy_category_vacancy',
            'wms_vacancy_attachments',
            'wms_catalog_categories',
            'wms_catalog_brands',
            'wms_catalog_promotions',
            'wms_catalog_products',
            'wms_catalog_category_product',
            'wms_catalog_product_images',
            'wms_catalog_product_attachments',
            'wms_catalog_product_options',
            'wms_catalog_product_translations',
            'wms_catalog_product_combinations',
            'wms_catalog_discounts',
            'wms_catalog_reviews',
            'wms_catalog_review_criteria',
            'wms_catalog_review_scores',
            'wms_catalog_product_videos',
            'wms_catalog_stock',
            'wms_catalog_coupons',
            'wms_shipping_costs',
            'wms_delivery_settings',
            'wms_delivery_days',
            'wms_delivery_dates',
            'wms_orders',
            'wms_order_items',
            'wms_mailing_contact_categories',
            'wms_mailing_contacts',
            'wms_mailing_contact_category_contact',
            'wms_mailing_contact_blacklist',
            'wms_mailing_contact_tokens',
            'wms_mailing_groups',
            'wms_mailing_group_contacts',
            'wms_mailing_group_categories',
            'wms_mailing_group_users',
            'wms_mailing_group_user_categories',
            'wms_mailing_opens',
            'wms_mail_data',
            'wms_mailings',
            'wms_mailing_categories',
            'wms_mailing_category_mailing',
            'wms_mailing_items',
            'wms_newsletter_categories',
            'wms_newsletters',
            'wms_newsletter_category_newsletter',
            'wms_newsletter_base_templates',
            'wms_newsletter_template_blocks',
            'wms_radio_guides',
            'wms_radio_programs',
            'wms_radio_schedules',
            'wms_redirects',
            'wms_urls',
            'wms_url_references',
            'wms_menu_categories',
            'wms_domains',
            'wms_domain_roles',
            'wms_user_categories',
            'wms_user_category_roles',
            'wms_user_logins',
            'wms_user_rights',
            'wms_user_sessions',
            'wms_user_category_user',
            'wms_login_cookies',
            'wms_password_reset_records',
            'wms_user_tokens',
            'wms_roles',
            'wms_permissions',
            'wms_role_permissions',
            'wms_rights',
            'wms_category_right_specifications',
            'wms_role_categories',
            'wms_role_content',
            'wms_role_languages',
            'wms_role_category_role',
            'wms_page_permissions',
            'wms_module_categories',
            'wms_module_category_module',
            'wms_rbac_logs',
            'wms_languages',
            'wms_iso_languages',
            'wms_countries',
            'wms_country_codes',
            'wms_country_payment_methods',
            'wms_translation_keys',
            'wms_translation_values',
            'wms_translations',
            'wms_search_keywords',
            'wms_guestbook_entries',
            'wms_geo_locations',
            'wms_short_links',
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
            $table->unsignedBigInteger('legacy_id')->nullable()->index();
            $table->json('legacy_payload')->nullable();
        });
    }

    private function createContentTables(): void
    {
        $this->createCategoryTable('wms_content_categories');
        $this->createPublishableTable('wms_content_items');
        $this->createPivotTable('wms_content_category_content_item', 'content_category_id', 'content_item_id');
        $this->createMediaTable('wms_content_images', 'content_item_id');
        $this->createAttachmentTable('wms_content_attachments', 'content_item_id');

        Schema::create('wms_content_block_layouts', function (Blueprint $table): void {
            $this->baseColumns($table);
            $table->string('name');
            $table->string('handle')->nullable()->index();
            $table->json('columns')->nullable();
            $this->timestamps($table);
        });

        Schema::create('wms_content_blocks', function (Blueprint $table): void {
            $this->baseColumns($table);
            $table->unsignedBigInteger('content_item_id')->nullable()->index();
            $table->unsignedBigInteger('layout_id')->nullable()->index();
            $table->string('name')->nullable();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->json('configuration')->nullable();
            $this->timestamps($table);
        });

        Schema::create('wms_content_block_part_containers', function (Blueprint $table): void {
            $this->baseColumns($table);
            $table->unsignedBigInteger('block_id')->nullable()->index();
            $table->string('region')->nullable()->index();
            $table->unsignedInteger('sort_order')->default(0);
            $this->timestamps($table);
        });

        Schema::create('wms_content_block_parts', function (Blueprint $table): void {
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
        $this->createCategoryTable('wms_banner_categories');
        $this->createSimpleMediaEntityTable('wms_banners');
        $this->createPivotTable('wms_banner_category_banner', 'banner_category_id', 'banner_id');
        $this->createTranslationTable('wms_banner_translations', 'banner_id');
    }

    private function createSliderTables(): void
    {
        $this->createCategoryTable('wms_slider_categories');
        $this->createSimpleMediaEntityTable('wms_sliders');
        $this->createPivotTable('wms_slider_category_slider', 'slider_category_id', 'slider_id');
    }

    private function createFormTables(): void
    {
        $this->createCategoryTable('wms_form_categories');

        Schema::create('wms_forms', function (Blueprint $table): void {
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

        $this->createPivotTable('wms_form_category_form', 'form_category_id', 'form_id');

        Schema::create('wms_form_blocks', function (Blueprint $table): void {
            $this->baseColumns($table);
            $table->unsignedBigInteger('form_id')->index();
            $table->string('title')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('settings')->nullable();
            $this->timestamps($table);
        });

        Schema::create('wms_form_rows', function (Blueprint $table): void {
            $this->baseColumns($table);
            $table->unsignedBigInteger('block_id')->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('settings')->nullable();
            $this->timestamps($table);
        });

        Schema::create('wms_form_fields', function (Blueprint $table): void {
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

        Schema::create('wms_form_field_options', function (Blueprint $table): void {
            $this->baseColumns($table);
            $table->unsignedBigInteger('field_id')->index();
            $table->string('label');
            $table->string('value')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $this->timestamps($table);
        });

        Schema::create('wms_form_messages', function (Blueprint $table): void {
            $this->baseColumns($table);
            $table->unsignedBigInteger('form_id')->index();
            $table->string('subject')->nullable();
            $table->longText('body')->nullable();
            $table->string('type')->default('notification')->index();
            $this->timestamps($table);
        });

        Schema::create('wms_form_message_fields', function (Blueprint $table): void {
            $this->baseColumns($table);
            $table->unsignedBigInteger('message_id')->index();
            $table->unsignedBigInteger('field_id')->index();
            $table->string('placeholder')->nullable();
            $this->timestamps($table);
        });

        Schema::create('wms_form_submissions', function (Blueprint $table): void {
            $this->baseColumns($table);
            $table->unsignedBigInteger('form_id')->index();
            $table->string('locale', 8)->nullable();
            $table->string('remote_ip')->nullable();
            $table->string('user_agent')->nullable();
            $table->json('payload')->nullable();
            $this->timestamps($table);
        });

        Schema::create('wms_form_submission_answers', function (Blueprint $table): void {
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
        $this->createCategoryTable('wms_event_categories');
        $this->createPublishableTable('wms_events');
        $this->createPivotTable('wms_event_category_event', 'event_category_id', 'event_id');
        $this->createMediaTable('wms_event_images', 'event_id');
        $this->createAttachmentTable('wms_event_attachments', 'event_id');

        Schema::create('wms_event_parts', function (Blueprint $table): void {
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
        $this->createCategoryTable('wms_faq_categories');
        $this->createPublishableTable('wms_faq_items', 'question');
        $this->createPivotTable('wms_faq_category_faq_item', 'faq_category_id', 'faq_item_id');
        $this->createMediaTable('wms_faq_images', 'faq_item_id');
        $this->createAttachmentTable('wms_faq_attachments', 'faq_item_id');
        $this->createVideoTable('wms_faq_videos', 'faq_item_id');
    }

    private function createDownloadTables(): void
    {
        $this->createCategoryTable('wms_download_categories');

        Schema::create('wms_downloads', function (Blueprint $table): void {
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

        $this->createPivotTable('wms_download_category_download', 'download_category_id', 'download_id');
    }

    private function createLocationTables(): void
    {
        $this->createCategoryTable('wms_location_categories');

        Schema::create('wms_locations', function (Blueprint $table): void {
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

        $this->createPivotTable('wms_location_category_location', 'location_category_id', 'location_id');
        $this->createMediaTable('wms_location_images', 'location_id');

        Schema::create('wms_location_opening_hours', function (Blueprint $table): void {
            $this->baseColumns($table);
            $table->unsignedBigInteger('location_id')->index();
            $table->string('day')->index();
            $table->time('opens_at')->nullable();
            $table->time('closes_at')->nullable();
            $table->boolean('is_closed')->default(false);
            $this->timestamps($table);
        });

        Schema::create('wms_location_special_opening_hours', function (Blueprint $table): void {
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
        $this->createCategoryTable('wms_vacancy_categories');
        $this->createPublishableTable('wms_vacancies');
        $this->createPivotTable('wms_vacancy_category_vacancy', 'vacancy_category_id', 'vacancy_id');
        $this->createAttachmentTable('wms_vacancy_attachments', 'vacancy_id');
    }

    private function createCatalogTables(): void
    {
        $this->createCategoryTable('wms_catalog_categories');
        $this->createSimpleNamedTable('wms_catalog_brands');
        $this->createSimpleNamedTable('wms_catalog_promotions');

        Schema::create('wms_catalog_products', function (Blueprint $table): void {
            $this->baseColumns($table);
            $table->string('sku')->nullable()->unique();
            $table->string('name')->index();
            $table->longText('description')->nullable();
            $table->integer('price')->default(0);
            $table->text('price_note')->nullable();
            $table->boolean('is_on_sale')->default(false)->index();
            $table->date('sale_starts_at')->nullable();
            $table->date('sale_ends_at')->nullable();
            $table->integer('sale_price')->nullable();
            $table->text('sale_price_note')->nullable();
            $table->text('meta_description')->nullable();
            $table->unsignedBigInteger('brand_id')->nullable()->index();
            $table->unsignedBigInteger('promotion_id')->nullable()->index();
            $table->boolean('can_be_engraved')->default(false);
            $table->string('status')->default('draft')->index();
            $table->date('active_from')->nullable();
            $table->date('active_until')->nullable();
            $table->json('metadata')->nullable();
            $this->timestamps($table);
            $table->softDeletes();
        });

        $this->createPivotTable('wms_catalog_category_product', 'catalog_category_id', 'catalog_product_id');
        $this->createMediaTable('wms_catalog_product_images', 'catalog_product_id');
        $this->createAttachmentTable('wms_catalog_product_attachments', 'catalog_product_id');

        Schema::create('wms_catalog_product_options', function (Blueprint $table): void {
            $this->baseColumns($table);
            $table->unsignedBigInteger('catalog_product_id')->index();
            $table->string('locale', 8)->default('nl')->index();
            $table->string('label');
            $table->longText('value')->nullable();
            $this->timestamps($table);
        });

        $this->createTranslationTable('wms_catalog_product_translations', 'catalog_product_id');
        $this->createSimpleRelationshipTable('wms_catalog_product_combinations', 'catalog_product_id', 'related_product_id');
        $this->createSimpleNamedTable('wms_catalog_discounts');

        Schema::create('wms_catalog_reviews', function (Blueprint $table): void {
            $this->baseColumns($table);
            $table->unsignedBigInteger('catalog_product_id')->index();
            $table->string('author_name')->nullable();
            $table->string('author_email')->nullable();
            $table->unsignedTinyInteger('rating')->nullable();
            $table->string('status')->default('pending')->index();
            $table->text('title')->nullable();
            $table->longText('content')->nullable();
            $this->timestamps($table);
            $table->softDeletes();
        });

        $this->createSimpleNamedTable('wms_catalog_review_criteria');
        $this->createSimpleRelationshipTable('wms_catalog_review_scores', 'catalog_review_id', 'catalog_review_criterion_id');
        $this->createVideoTable('wms_catalog_product_videos', 'catalog_product_id');

        Schema::create('wms_catalog_stock', function (Blueprint $table): void {
            $this->baseColumns($table);
            $table->unsignedBigInteger('catalog_product_id')->index();
            $table->integer('quantity')->default(0);
            $table->string('location')->nullable();
            $this->timestamps($table);
        });

        Schema::create('wms_catalog_coupons', function (Blueprint $table): void {
            $this->baseColumns($table);
            $table->string('name');
            $table->string('code')->unique();
            $table->unsignedTinyInteger('percentage_discount')->default(0);
            $table->integer('minimum_amount')->default(0);
            $table->date('starts_at')->nullable();
            $table->date('ends_at')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->string('usage_mode')->default('any');
            $this->timestamps($table);
        });

        Schema::create('wms_shipping_costs', function (Blueprint $table): void {
            $this->baseColumns($table);
            $table->string('country_code')->unique();
            $table->text('description')->nullable();
            $table->integer('shipping_cost')->nullable();
            $this->timestamps($table);
        });
    }

    private function createOrderTables(): void
    {
        Schema::create('wms_delivery_settings', function (Blueprint $table): void {
            $this->baseColumns($table);
            $table->string('name')->nullable();
            $table->json('settings')->nullable();
            $this->timestamps($table);
        });

        Schema::create('wms_delivery_days', function (Blueprint $table): void {
            $this->baseColumns($table);
            $table->unsignedBigInteger('delivery_setting_id')->nullable()->index();
            $table->string('day')->index();
            $table->boolean('is_available')->default(true);
            $this->timestamps($table);
        });

        Schema::create('wms_delivery_dates', function (Blueprint $table): void {
            $this->baseColumns($table);
            $table->unsignedBigInteger('delivery_setting_id')->nullable()->index();
            $table->date('date')->index();
            $table->boolean('is_available')->default(true);
            $table->text('note')->nullable();
            $this->timestamps($table);
        });

        Schema::create('wms_orders', function (Blueprint $table): void {
            $this->baseColumns($table);
            $table->string('order_number')->unique();
            $table->string('status')->default('new')->index();
            $table->string('payment_status')->nullable()->index();
            $table->string('delivery_status')->nullable()->index();
            $table->string('customer_email')->nullable()->index();
            $table->string('customer_name')->nullable();
            $table->integer('subtotal')->default(0);
            $table->integer('tax_total')->default(0);
            $table->integer('shipping_total')->default(0);
            $table->integer('discount_total')->default(0);
            $table->integer('grand_total')->default(0);
            $table->json('billing_address')->nullable();
            $table->json('shipping_address')->nullable();
            $table->json('metadata')->nullable();
            $this->timestamps($table);
            $table->softDeletes();
        });

        Schema::create('wms_order_items', function (Blueprint $table): void {
            $this->baseColumns($table);
            $table->unsignedBigInteger('order_id')->index();
            $table->unsignedBigInteger('catalog_product_id')->nullable()->index();
            $table->string('sku')->nullable();
            $table->string('name');
            $table->integer('unit_price')->default(0);
            $table->integer('quantity')->default(1);
            $table->integer('line_total')->default(0);
            $table->json('metadata')->nullable();
            $this->timestamps($table);
        });
    }

    private function createMailingTables(): void
    {
        $this->createCategoryTable('wms_mailing_contact_categories');

        Schema::create('wms_mailing_contacts', function (Blueprint $table): void {
            $this->baseColumns($table);
            $table->string('email')->index();
            $table->string('first_name')->nullable();
            $table->string('middle_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('company_name')->nullable();
            $table->string('locale', 8)->nullable()->index();
            $table->boolean('is_active')->default(true)->index();
            $table->json('metadata')->nullable();
            $this->timestamps($table);
            $table->softDeletes();
        });

        $this->createPivotTable('wms_mailing_contact_category_contact', 'mailing_contact_category_id', 'mailing_contact_id');

        Schema::create('wms_mailing_contact_blacklist', function (Blueprint $table): void {
            $this->baseColumns($table);
            $table->string('email')->unique();
            $table->text('reason')->nullable();
            $this->timestamps($table);
        });

        Schema::create('wms_mailing_contact_tokens', function (Blueprint $table): void {
            $this->baseColumns($table);
            $table->unsignedBigInteger('mailing_contact_id')->index();
            $table->string('token')->unique();
            $table->string('type')->nullable()->index();
            $table->timestamp('expires_at')->nullable();
            $this->timestamps($table);
        });

        $this->createSimpleNamedTable('wms_mailing_groups');
        $this->createSimpleRelationshipTable('wms_mailing_group_contacts', 'mailing_group_id', 'mailing_contact_id');
        $this->createSimpleRelationshipTable('wms_mailing_group_categories', 'mailing_group_id', 'mailing_contact_category_id');
        $this->createSimpleRelationshipTable('wms_mailing_group_users', 'mailing_group_id', 'user_id');
        $this->createSimpleRelationshipTable('wms_mailing_group_user_categories', 'mailing_group_id', 'user_category_id');

        Schema::create('wms_mailing_opens', function (Blueprint $table): void {
            $this->baseColumns($table);
            $table->unsignedBigInteger('mailing_id')->nullable()->index();
            $table->unsignedBigInteger('mailing_contact_id')->nullable()->index();
            $table->string('remote_ip')->nullable();
            $table->timestamp('opened_at')->nullable()->index();
            $this->timestamps($table);
        });

        $this->createSimpleNamedTable('wms_mail_data');
        $this->createSimpleNamedTable('wms_mailings');
        $this->createCategoryTable('wms_mailing_categories');
        $this->createPivotTable('wms_mailing_category_mailing', 'mailing_category_id', 'mailing_id');

        Schema::create('wms_mailing_items', function (Blueprint $table): void {
            $this->baseColumns($table);
            $table->unsignedBigInteger('mailing_id')->index();
            $table->string('title')->nullable();
            $table->longText('content')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('metadata')->nullable();
            $this->timestamps($table);
        });
    }

    private function createNewsletterTables(): void
    {
        $this->createCategoryTable('wms_newsletter_categories');
        $this->createSimpleNamedTable('wms_newsletters');
        $this->createPivotTable('wms_newsletter_category_newsletter', 'newsletter_category_id', 'newsletter_id');
        $this->createSimpleNamedTable('wms_newsletter_base_templates');

        Schema::create('wms_newsletter_template_blocks', function (Blueprint $table): void {
            $this->baseColumns($table);
            $table->unsignedBigInteger('newsletter_base_template_id')->nullable()->index();
            $table->string('title')->nullable();
            $table->longText('content')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('metadata')->nullable();
            $this->timestamps($table);
        });
    }

    private function createRadioTables(): void
    {
        $this->createSimpleNamedTable('wms_radio_guides');
        $this->createPublishableTable('wms_radio_programs');

        Schema::create('wms_radio_schedules', function (Blueprint $table): void {
            $this->baseColumns($table);
            $table->unsignedBigInteger('radio_guide_id')->nullable()->index();
            $table->unsignedBigInteger('radio_program_id')->nullable()->index();
            $table->string('day')->nullable()->index();
            $table->time('starts_at')->nullable();
            $table->time('ends_at')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('metadata')->nullable();
            $this->timestamps($table);
        });
    }

    private function createPlatformTables(): void
    {
        Schema::create('wms_redirects', function (Blueprint $table): void {
            $this->baseColumns($table);
            $table->string('source_path')->unique();
            $table->string('target_url');
            $table->unsignedSmallInteger('status_code')->default(301);
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('preserve_query')->default(false);
            $this->timestamps($table);
        });

        Schema::create('wms_urls', function (Blueprint $table): void {
            $this->baseColumns($table);
            $table->string('path')->unique();
            $table->string('target_type')->nullable()->index();
            $table->unsignedBigInteger('target_id')->nullable()->index();
            $table->string('locale', 8)->nullable()->index();
            $table->boolean('is_restricted')->default(false);
            $table->json('metadata')->nullable();
            $this->timestamps($table);
        });

        Schema::create('wms_url_references', function (Blueprint $table): void {
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

        $this->createCategoryTable('wms_menu_categories');

        Schema::create('wms_domains', function (Blueprint $table): void {
            $this->baseColumns($table);
            $table->string('host')->unique();
            $table->string('name')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->json('settings')->nullable();
            $this->timestamps($table);
        });

        $this->createSimpleRelationshipTable('wms_domain_roles', 'domain_id', 'role_id');
        $this->createCategoryTable('wms_user_categories');
        $this->createSimpleRelationshipTable('wms_user_category_roles', 'user_category_id', 'role_id');

        Schema::create('wms_user_logins', function (Blueprint $table): void {
            $this->baseColumns($table);
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->date('login_date')->nullable();
            $table->time('login_time')->nullable();
            $table->string('remote_ip')->nullable();
            $table->string('host')->nullable();
            $this->timestamps($table);
        });

        Schema::create('wms_user_rights', function (Blueprint $table): void {
            $this->baseColumns($table);
            $table->unsignedBigInteger('user_category_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('object_type')->nullable()->index();
            $table->unsignedBigInteger('object_id')->nullable()->index();
            $table->boolean('can_read')->default(false);
            $table->boolean('can_write')->default(false);
            $table->boolean('can_edit')->default(false);
            $table->boolean('can_delete')->default(false);
        });

        Schema::create('wms_user_sessions', function (Blueprint $table): void {
            $table->string('session_id')->primary();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->unsignedInteger('last_activity')->nullable();
            $table->boolean('is_idle')->default(false);
            $this->timestamps($table);
        });

        $this->createSimpleRelationshipTable('wms_user_category_user', 'user_id', 'user_category_id');
        $this->createTokenTable('wms_login_cookies', 'user_id');
        $this->createTokenTable('wms_password_reset_records', 'user_id');
        $this->createTokenTable('wms_user_tokens', 'user_id');
        $this->createSimpleNamedTable('wms_roles');
        $this->createSimpleNamedTable('wms_permissions');
        $this->createSimpleRelationshipTable('wms_role_permissions', 'role_id', 'permission_id');
        $this->createSimpleNamedTable('wms_rights');
        $this->createSimpleRelationshipTable('wms_category_right_specifications', 'right_id', 'category_id');
        $this->createCategoryTable('wms_role_categories');
        $this->createSimpleRelationshipTable('wms_role_content', 'role_id', 'content_item_id');
        $this->createSimpleRelationshipTable('wms_role_languages', 'role_id', 'language_id');
        $this->createSimpleRelationshipTable('wms_role_category_role', 'role_id', 'role_category_id');

        Schema::create('wms_page_permissions', function (Blueprint $table): void {
            $this->baseColumns($table);
            $table->unsignedBigInteger('role_id')->index();
            $table->string('page')->index();
            $this->timestamps($table);
        });

        $this->createCategoryTable('wms_module_categories');
        $this->createSimpleRelationshipTable('wms_module_category_module', 'module_category_id', 'module_id');

        Schema::create('wms_rbac_logs', function (Blueprint $table): void {
            $this->baseColumns($table);
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('action')->index();
            $table->string('object_type')->nullable()->index();
            $table->unsignedBigInteger('object_id')->nullable()->index();
            $table->json('payload')->nullable();
            $this->timestamps($table);
        });
    }

    private function createLocalizationTables(): void
    {
        $this->createSimpleNamedTable('wms_languages');
        $this->createSimpleNamedTable('wms_iso_languages');
        $this->createSimpleNamedTable('wms_countries');

        Schema::create('wms_country_codes', function (Blueprint $table): void {
            $this->baseColumns($table);
            $table->string('code')->index();
            $table->string('country_code')->nullable()->index();
            $table->string('name')->nullable();
            $table->json('metadata')->nullable();
            $this->timestamps($table);
        });

        $this->createSimpleRelationshipTable('wms_country_payment_methods', 'country_id', 'payment_method_id');

        Schema::create('wms_translation_keys', function (Blueprint $table): void {
            $this->baseColumns($table);
            $table->string('key')->unique();
            $table->string('group')->nullable()->index();
            $table->text('source_text')->nullable();
            $this->timestamps($table);
        });

        Schema::create('wms_translation_values', function (Blueprint $table): void {
            $this->baseColumns($table);
            $table->unsignedBigInteger('translation_key_id')->index();
            $table->string('locale', 8)->index();
            $table->longText('value')->nullable();
            $this->timestamps($table);
        });

        Schema::create('wms_translations', function (Blueprint $table): void {
            $this->baseColumns($table);
            $table->string('locale', 8)->index();
            $table->string('key')->index();
            $table->longText('value')->nullable();
            $table->json('metadata')->nullable();
            $this->timestamps($table);
        });
    }

    private function createUtilityTables(): void
    {
        Schema::create('wms_search_keywords', function (Blueprint $table): void {
            $this->baseColumns($table);
            $table->string('keyword')->index();
            $table->string('remote_ip')->nullable();
            $table->unsignedBigInteger('domain_id')->nullable()->index();
            $this->timestamps($table);
        });

        Schema::create('wms_guestbook_entries', function (Blueprint $table): void {
            $this->baseColumns($table);
            $table->string('author_name')->nullable();
            $table->string('author_email')->nullable();
            $table->longText('message')->nullable();
            $table->string('status')->default('pending')->index();
            $this->timestamps($table);
            $table->softDeletes();
        });

        Schema::create('wms_geo_locations', function (Blueprint $table): void {
            $this->baseColumns($table);
            $table->string('name')->nullable();
            $table->string('latitude')->nullable();
            $table->string('longitude')->nullable();
            $table->json('metadata')->nullable();
            $this->timestamps($table);
        });

        Schema::create('wms_short_links', function (Blueprint $table): void {
            $this->baseColumns($table);
            $table->string('real_url');
            $table->string('short_url')->unique();
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
            $table->unsignedBigInteger($leftColumn)->index();
            $table->unsignedBigInteger($rightColumn)->index();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $this->timestamps($table);
            $table->unique([$leftColumn, $rightColumn], $tableName.'_unique');
        });
    }

    private function createSimpleRelationshipTable(string $tableName, string $leftColumn, string $rightColumn): void
    {
        Schema::create($tableName, function (Blueprint $table) use ($leftColumn, $rightColumn): void {
            $this->baseColumns($table);
            $table->unsignedBigInteger($leftColumn)->index();
            $table->unsignedBigInteger($rightColumn)->index();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->json('metadata')->nullable();
            $this->timestamps($table);
        });
    }

    private function createTokenTable(string $tableName, string $ownerColumn): void
    {
        Schema::create($tableName, function (Blueprint $table) use ($ownerColumn): void {
            $this->baseColumns($table);
            $table->unsignedBigInteger($ownerColumn)->nullable()->index();
            $table->string('token')->unique();
            $table->string('type')->nullable()->index();
            $table->timestamp('expires_at')->nullable();
            $table->json('metadata')->nullable();
            $this->timestamps($table);
        });
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
