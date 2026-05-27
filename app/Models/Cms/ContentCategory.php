<?php

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContentCategory extends CmsModel
{
    use SoftDeletes;

    protected $table = 'content_categories';

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

    public function contentItems(): BelongsToMany
    {
        return $this->belongsToMany(ContentItem::class, 'content_category_content_item')
            ->withPivot('sort_order')
            ->withTimestamps()
            ->orderBy('content_category_content_item.sort_order')
            ->orderBy('content_items.title');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ContentCategoryImage::class)->orderBy('sort_order')->orderBy('id');
    }
}
