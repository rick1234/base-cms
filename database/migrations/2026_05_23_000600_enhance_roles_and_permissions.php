<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table): void {
            if (! Schema::hasColumn('roles', 'is_system')) {
                $table->boolean('is_system')->default(false)->after('status');
            }
        });

        Schema::table('permissions', function (Blueprint $table): void {
            if (! Schema::hasColumn('permissions', 'permission_key')) {
                $table->string('permission_key')->nullable()->unique()->after('slug');
            }

            if (! Schema::hasColumn('permissions', 'module_key')) {
                $table->string('module_key')->nullable()->index()->after('permission_key');
            }

            if (! Schema::hasColumn('permissions', 'permission_type')) {
                $table->string('permission_type')->default('module')->index()->after('module_key');
            }

            if (! Schema::hasColumn('permissions', 'target')) {
                $table->string('target')->nullable()->index()->after('permission_type');
            }

            if (! Schema::hasColumn('permissions', 'action')) {
                $table->string('action')->nullable()->index()->after('target');
            }

            if (! Schema::hasColumn('permissions', 'label')) {
                $table->string('label')->nullable()->after('action');
            }

            if (! Schema::hasColumn('permissions', 'is_system')) {
                $table->boolean('is_system')->default(true)->after('status');
            }
        });

        if (! Schema::hasTable('role_user')) {
            Schema::create('role_user', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('role_id')->index();
                $table->unsignedBigInteger('user_id')->index();
                $table->boolean('is_primary')->default(false);
                $table->timestamps();
                $table->unique(['role_id', 'user_id'], 'role_user_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('role_user');

        Schema::table('permissions', function (Blueprint $table): void {
            foreach ([
                'permission_key',
                'module_key',
                'permission_type',
                'target',
                'action',
                'label',
                'is_system',
            ] as $column) {
                if (Schema::hasColumn('permissions', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('roles', function (Blueprint $table): void {
            if (Schema::hasColumn('roles', 'is_system')) {
                $table->dropColumn('is_system');
            }
        });
    }
};
