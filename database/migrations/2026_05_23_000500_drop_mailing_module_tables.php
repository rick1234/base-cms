<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('cms_modules')) {
            DB::table('cms_modules')
                ->where('handle', 'mailing_contacts')
                ->delete();
        }

        foreach ([
            'mailing_items',
            'mailing_category_mailing',
            'mailing_categories',
            'mailings',
            'mail_data',
            'mailing_opens',
            'mailing_group_user_categories',
            'mailing_group_users',
            'mailing_group_categories',
            'mailing_group_contacts',
            'mailing_groups',
            'mailing_contact_tokens',
            'mailing_contact_blacklist',
            'mailing_contact_category_contact',
            'mailing_contacts',
            'mailing_contact_categories',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }

    public function down(): void
    {
        $this->createCategoryTable('mailing_contact_categories');

        Schema::create('mailing_contacts', function (Blueprint $table): void {
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

        $this->createPivotTable('mailing_contact_category_contact', 'mailing_contact_category_id', 'mailing_contact_id');

        Schema::create('mailing_contact_blacklist', function (Blueprint $table): void {
            $this->baseColumns($table);
            $table->string('email')->unique();
            $table->text('reason')->nullable();
            $this->timestamps($table);
        });

        Schema::create('mailing_contact_tokens', function (Blueprint $table): void {
            $this->baseColumns($table);
            $table->unsignedBigInteger('mailing_contact_id')->index();
            $table->string('token')->unique();
            $table->string('type')->nullable()->index();
            $table->timestamp('expires_at')->nullable();
            $this->timestamps($table);
        });

        $this->createSimpleNamedTable('mailing_groups');
        $this->createSimpleRelationshipTable('mailing_group_contacts', 'mailing_group_id', 'mailing_contact_id');
        $this->createSimpleRelationshipTable('mailing_group_categories', 'mailing_group_id', 'mailing_contact_category_id');
        $this->createSimpleRelationshipTable('mailing_group_users', 'mailing_group_id', 'user_id');
        $this->createSimpleRelationshipTable('mailing_group_user_categories', 'mailing_group_id', 'user_category_id');

        Schema::create('mailing_opens', function (Blueprint $table): void {
            $this->baseColumns($table);
            $table->unsignedBigInteger('mailing_id')->nullable()->index();
            $table->unsignedBigInteger('mailing_contact_id')->nullable()->index();
            $table->string('remote_ip')->nullable();
            $table->timestamp('opened_at')->nullable()->index();
            $this->timestamps($table);
        });

        $this->createSimpleNamedTable('mail_data');
        $this->createSimpleNamedTable('mailings');
        $this->createCategoryTable('mailing_categories');
        $this->createPivotTable('mailing_category_mailing', 'mailing_category_id', 'mailing_id');

        Schema::create('mailing_items', function (Blueprint $table): void {
            $this->baseColumns($table);
            $table->unsignedBigInteger('mailing_id')->index();
            $table->string('title')->nullable();
            $table->longText('content')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('metadata')->nullable();
            $this->timestamps($table);
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
