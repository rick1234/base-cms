<?php

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Form extends CmsModel
{
    use SoftDeletes;

    protected $table = 'forms';

    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'settings' => 'array',
        ];
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(FormCategory::class, 'form_category_form')
            ->withPivot('sort_order')
            ->withTimestamps()
            ->orderBy('form_category_form.sort_order')
            ->orderBy('form_categories.name');
    }

    public function blocks(): HasMany
    {
        return $this->hasMany(FormBlock::class, 'form_id')->orderBy('sort_order')->orderBy('id');
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(FormRecipient::class, 'form_id')->orderBy('sort_order')->orderBy('id');
    }

    public function activeRecipients(): HasMany
    {
        return $this->recipients()->where('is_active', true);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(FormMessage::class, 'form_id')->orderBy('sort_order')->orderBy('id');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(FormSubmission::class, 'form_id')->latest();
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    public function isActive(): bool
    {
        return $this->status === 'published';
    }

    /**
     * @return array<string, string>
     */
    public static function fieldTypes(): array
    {
        return [
            'input' => __('Tekstveld'),
            'textarea' => __('Tekstblok'),
            'email' => __('E-mail'),
            'file' => __('Bestand'),
            'radio' => __('Radio'),
            'select' => __('Select'),
            'checkbox' => __('Checkbox'),
            'image-set-choice' => __('Afbeelding keuze'),
            'title' => __('Titel'),
            'paragraph' => __('Paragraaf'),
            'horizontal-rule' => __('Horizontale lijn'),
            'date' => __('Datum'),
            'number' => __('Nummer'),
            'phone' => __('Telefoon'),
        ];
    }

    public function placeholderDocumentation(): array
    {
        return $this->blocks
            ->flatMap(fn (FormBlock $block) => $block->rows)
            ->flatMap(fn (FormRow $row) => $row->fields)
            ->mapWithKeys(fn (FormField $field): array => [
                $field->placeholderKey() => $field->label ?: $field->name,
            ])
            ->all();
    }
}
