<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('forms') || ! Schema::hasColumn('forms', 'settings')) {
            return;
        }

        DB::table('forms')
            ->select(['id', 'settings'])
            ->chunkById(100, function ($forms): void {
                foreach ($forms as $form) {
                    $settings = $this->decodeSettings($form->settings);

                    if ($settings === null) {
                        continue;
                    }

                    if (($settings['mail_template'] ?? null) === 'forms.default') {
                        $settings['mail_template'] = 'mail.forms.submission';
                    }

                    unset($settings['honeypot_enabled'], $settings['honeypot_field']);

                    DB::table('forms')
                        ->where('id', $form->id)
                        ->update(['settings' => json_encode($settings, JSON_THROW_ON_ERROR)]);
                }
            });
    }

    public function down(): void
    {
        //
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
