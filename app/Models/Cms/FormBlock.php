<?php

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FormBlock extends CmsModel
{
    protected $table = 'form_blocks';

    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'settings' => 'array',
        ];
    }

    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class, 'form_id');
    }

    public function rows(): HasMany
    {
        return $this->hasMany(FormRow::class, 'block_id')->orderBy('sort_order')->orderBy('id');
    }
}
