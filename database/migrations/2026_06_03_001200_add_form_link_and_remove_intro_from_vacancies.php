<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vacancies', function (Blueprint $table): void {
            if (! Schema::hasColumn('vacancies', 'form_id')) {
                $after = Schema::hasColumn('vacancies', 'meta_description') ? 'meta_description' : 'body';
                $table->unsignedBigInteger('form_id')->nullable()->index()->after($after);
            }
        });

        $this->removeApplicationUrls();

        Schema::table('vacancies', function (Blueprint $table): void {
            if (Schema::hasColumn('vacancies', 'intro')) {
                $table->dropColumn('intro');
            }
        });
    }

    public function down(): void
    {
        Schema::table('vacancies', function (Blueprint $table): void {
            if (! Schema::hasColumn('vacancies', 'intro')) {
                $table->text('intro')->nullable()->after('locale');
            }

            if (Schema::hasColumn('vacancies', 'form_id')) {
                $table->dropColumn('form_id');
            }
        });
    }

    private function removeApplicationUrls(): void
    {
        if (! Schema::hasColumn('vacancies', 'metadata')) {
            return;
        }

        DB::table('vacancies')
            ->select(['id', 'metadata'])
            ->whereNotNull('metadata')
            ->orderBy('id')
            ->get()
            ->each(function (object $vacancy): void {
                $metadata = json_decode((string) $vacancy->metadata, true);

                if (! is_array($metadata) || ! array_key_exists('application_url', $metadata)) {
                    return;
                }

                unset($metadata['application_url']);

                DB::table('vacancies')
                    ->where('id', $vacancy->id)
                    ->update([
                        'metadata' => $metadata === [] ? null : json_encode($metadata),
                    ]);
            });
    }
};
