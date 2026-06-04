<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('download_access_tokens', function (Blueprint $table): void {
            if (! Schema::hasColumn('download_access_tokens', 'email')) {
                $table->string('email')->nullable()->after('token_hash')->index();
            }

            if (! Schema::hasColumn('download_access_tokens', 'purpose')) {
                $table->string('purpose')->default('link')->after('email')->index();
            }

            if (! Schema::hasColumn('download_access_tokens', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable()->after('purpose')->index();
            }

            if (! Schema::hasColumn('download_access_tokens', 'first_ip_address')) {
                $table->string('first_ip_address', 45)->nullable()->after('used_count');
            }

            if (! Schema::hasColumn('download_access_tokens', 'last_ip_address')) {
                $table->string('last_ip_address', 45)->nullable()->after('first_ip_address');
            }

            if (! Schema::hasColumn('download_access_tokens', 'last_user_agent')) {
                $table->text('last_user_agent')->nullable()->after('last_ip_address');
            }
        });
    }

    public function down(): void
    {
        Schema::table('download_access_tokens', function (Blueprint $table): void {
            foreach ([
                'last_user_agent',
                'last_ip_address',
                'first_ip_address',
                'created_by',
                'purpose',
                'email',
            ] as $column) {
                if (Schema::hasColumn('download_access_tokens', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
