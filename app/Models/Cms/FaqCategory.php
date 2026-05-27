<?php

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FaqCategory extends CmsModel
{
    use SoftDeletes;

    protected $table = 'faq_categories';

    protected function casts(): array
    {
        return [
            ...parent::casts(),
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

    public function faqItems(): BelongsToMany
    {
        return $this->belongsToMany(FaqItem::class, 'faq_category_faq_item')
            ->withPivot('sort_order')
            ->withTimestamps()
            ->orderBy('faq_category_faq_item.sort_order')
            ->orderBy('faq_items.question');
    }
}
