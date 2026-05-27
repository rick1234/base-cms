<?php

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FormRow extends CmsModel
{
    protected $table = 'form_rows';

    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'settings' => 'array',
        ];
    }

    public function block(): BelongsTo
    {
        return $this->belongsTo(FormBlock::class, 'block_id');
    }

    public function fields(): HasMany
    {
        return $this->hasMany(FormField::class, 'row_id')->orderBy('sort_order')->orderBy('id');
    }
}
