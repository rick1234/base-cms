<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table): void {
            if (! Schema::hasColumn('events', 'subtitle')) {
                $table->string('subtitle')->nullable()->after('title');
            }

            if (! Schema::hasColumn('events', 'form_id')) {
                $table->unsignedBigInteger('form_id')->nullable()->index()->after('meta_description');
            }

            if (! Schema::hasColumn('events', 'starts_at')) {
                $table->date('starts_at')->nullable()->index()->after('form_id');
            }

            if (! Schema::hasColumn('events', 'ends_at')) {
                $table->date('ends_at')->nullable()->after('starts_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table): void {
            $columns = array_values(array_filter([
                Schema::hasColumn('events', 'subtitle') ? 'subtitle' : null,
                Schema::hasColumn('events', 'form_id') ? 'form_id' : null,
                Schema::hasColumn('events', 'starts_at') ? 'starts_at' : null,
                Schema::hasColumn('events', 'ends_at') ? 'ends_at' : null,
            ]));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
