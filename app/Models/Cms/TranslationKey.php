<?php

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TranslationKey extends CmsModel
{
    protected $table = 'translation_keys';

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'is_system' => 'boolean',
            'last_seen_at' => 'datetime',
        ]);
    }

    public function values(): HasMany
    {
        return $this->hasMany(TranslationValue::class, 'translation_key_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function normalizedGroup(): string
    {
        return $this->group ?: '*';
    }

    public function label(): string
    {
        return $this->normalizedGroup() === '*'
            ? $this->key
            : $this->normalizedGroup().'.'.$this->key;
    }

    public function valueFor(string $locale): ?TranslationValue
    {
        return $this->values->firstWhere('locale', $locale);
    }
}
