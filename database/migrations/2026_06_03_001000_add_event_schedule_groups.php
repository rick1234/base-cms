<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('event_schedule_groups')) {
            Schema::create('event_schedule_groups', function (Blueprint $table): void {
                $table->id();
                $table->uuid('uuid')->nullable()->unique();
                $table->unsignedBigInteger('legacy_id')->nullable()->index();
                $table->unsignedBigInteger('event_id')->index();
                $table->string('name');
                $table->unsignedInteger('sort_order')->default(0)->index();
                $table->boolean('is_collapsed')->default(false);
                $table->foreignId('created_by')->nullable()->index();
                $table->foreignId('updated_by')->nullable()->index();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('event_parts') && ! Schema::hasColumn('event_parts', 'event_schedule_group_id')) {
            Schema::table('event_parts', function (Blueprint $table): void {
                $table->unsignedBigInteger('event_schedule_group_id')->nullable()->after('event_id')->index();
            });
        }

        $this->groupExistingParts();
    }

    public function down(): void
    {
        if (Schema::hasTable('event_parts') && Schema::hasColumn('event_parts', 'event_schedule_group_id')) {
            Schema::table('event_parts', function (Blueprint $table): void {
                $table->dropColumn('event_schedule_group_id');
            });
        }

        Schema::dropIfExists('event_schedule_groups');
    }

    private function groupExistingParts(): void
    {
        if (! Schema::hasTable('event_parts') || ! Schema::hasTable('event_schedule_groups')) {
            return;
        }

        $eventIds = DB::table('event_parts')
            ->whereNull('event_schedule_group_id')
            ->whereNotNull('event_id')
            ->distinct()
            ->pluck('event_id');

        foreach ($eventIds as $eventId) {
            $event = DB::table('events')->where('id', $eventId)->first();
            $now = now();
            $groupId = DB::table('event_schedule_groups')->insertGetId([
                'uuid' => (string) Str::uuid(),
                'event_id' => $eventId,
                'name' => (string) (($event?->locale ?? null) === 'nl' ? 'Programma' : 'Schedule'),
                'sort_order' => 1,
                'is_collapsed' => false,
                'created_by' => $event?->created_by,
                'updated_by' => $event?->updated_by,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('event_parts')
                ->where('event_id', $eventId)
                ->whereNull('event_schedule_group_id')
                ->update(['event_schedule_group_id' => $groupId]);
        }
    }
};
