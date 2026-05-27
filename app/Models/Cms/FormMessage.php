<?php

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormMessage extends CmsModel
{
    protected $table = 'form_messages';

    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'is_active' => 'boolean',
            'settings' => 'array',
        ];
    }

    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class, 'form_id');
    }
}
