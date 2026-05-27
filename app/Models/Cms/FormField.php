<?php

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FormField extends CmsModel
{
    protected $table = 'form_fields';

    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'is_required' => 'boolean',
            'settings' => 'array',
            'validation_rules' => 'array',
        ];
    }

    public function row(): BelongsTo
    {
        return $this->belongsTo(FormRow::class, 'row_id');
    }

    public function options(): HasMany
    {
        return $this->hasMany(FormFieldOption::class, 'field_id')->orderBy('sort_order')->orderBy('id');
    }

    public function placeholderKey(): string
    {
        return '{'.$this->name.'}';
    }

    public function supportsOptions(): bool
    {
        return in_array($this->type, ['select', 'radio', 'checkbox', 'image-set-choice', 'image_set_choice'], true);
    }

    public function acceptsSubmissionValue(): bool
    {
        return ! in_array($this->type, ['title', 'paragraph', 'horizontal-rule'], true);
    }
}
