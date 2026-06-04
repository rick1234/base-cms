<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->cleanSettings('website_templates', 'default_settings');
        $this->cleanSettings('domains', 'template_settings');
    }

    public function down(): void
    {
        //
    }

    private function cleanSettings(string $table, string $column): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return;
        }

        DB::table($table)
            ->select(['id', $column])
            ->chunkById(100, function ($records) use ($table, $column): void {
                foreach ($records as $record) {
                    $settings = $this->decodeSettings($record->{$column});

                    if ($settings === null) {
                        continue;
                    }

                    $label = strtolower(trim((string) ($settings['footer_credit_label'] ?? '')));
                    $url = strtolower(trim((string) ($settings['footer_credit_url'] ?? '')));
                    $hasLegacyCredit = str_contains($label, 'hpu') || str_contains($url, 'hpu.nl');

                    if (! $hasLegacyCredit) {
                        continue;
                    }

                    unset($settings['footer_credit_label'], $settings['footer_credit_url']);

                    $settings['show_footer_credit'] = false;

                    DB::table($table)
                        ->where('id', $record->id)
                        ->update([$column => json_encode($settings, JSON_THROW_ON_ERROR)]);
                }
            });
    }

    /**
     * @return array<string, mixed>|null
     */
    private function decodeSettings(mixed $value): ?array
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_array($value)) {
            return $value;
        }

        $decoded = json_decode((string) $value, true, flags: JSON_THROW_ON_ERROR);

        return is_array($decoded) ? $decoded : null;
    }
};
