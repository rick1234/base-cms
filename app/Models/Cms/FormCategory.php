<?php

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FormCategory extends CmsModel
{
    use SoftDeletes;

    protected $table = 'form_categories';

    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'metadata' => 'array',
            'is_hidden_from_navigation' => 'boolean',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order')->orderBy('name');
    }

    public function forms(): BelongsToMany
    {
        return $this->belongsToMany(Form::class, 'form_category_form')
            ->withPivot('sort_order')
            ->withTimestamps()
            ->orderBy('form_category_form.sort_order')
            ->orderBy('forms.name');
    }
}
