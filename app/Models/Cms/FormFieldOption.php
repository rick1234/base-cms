<?php

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormFieldOption extends CmsModel
{
    protected $table = 'form_field_options';

    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'settings' => 'array',
        ];
    }

    public function field(): BelongsTo
    {
        return $this->belongsTo(FormField::class, 'field_id');
    }
}
