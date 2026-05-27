<?php

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TranslationValue extends CmsModel
{
    protected $table = 'translation_values';

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'is_reviewed' => 'boolean',
            'reviewed_at' => 'datetime',
        ]);
    }

    public function key(): BelongsTo
    {
        return $this->belongsTo(TranslationKey::class, 'translation_key_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }
}
