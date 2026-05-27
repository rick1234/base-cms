<?php

namespace App\Actions\Admin\Translations;

use App\Models\Cms\TranslationKey;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UpsertTranslationKey
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data, ?User $user, ?TranslationKey $translationKey = null): TranslationKey
    {
        return DB::transaction(function () use ($data, $user, $translationKey): TranslationKey {
            $translationKey ??= new TranslationKey;

            if (! $translationKey->exists) {
                $translationKey->created_by = $user?->id;
            }

            $translationKey->fill([
                'area' => $data['area'],
                'group' => $data['group'],
                'key' => $data['key'],
                'source_text' => $data['source_text'] ?? null,
                'source_locale' => $data['source_locale'],
                'description' => $data['description'] ?? null,
                'status' => $data['status'],
                'is_system' => $data['is_system'] ?? false,
            ]);
            $translationKey->updated_by = $user?->id;
            $translationKey->save();

            foreach ($data['values'] ?? [] as $locale => $valueData) {
                $translationValue = $translationKey->values()
                    ->where('locale', $locale)
                    ->first();

                if (! $translationValue) {
                    $translationValue = $translationKey->values()->make([
                        'locale' => $locale,
                        'created_by' => $user?->id,
                    ]);
                }

                $translationValue->fill([
                    'value' => $valueData['value'] ?? null,
                    'status' => 'active',
                    'is_reviewed' => (bool) ($valueData['is_reviewed'] ?? false),
                    'reviewed_at' => ($valueData['is_reviewed'] ?? false) ? now() : null,
                    'updated_by' => $user?->id,
                ])->save();
            }

            return $translationKey->refresh();
        });
    }
}
