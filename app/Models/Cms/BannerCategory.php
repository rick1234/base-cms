<?php

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class BannerCategory extends CmsModel
{
    use SoftDeletes;

    protected $table = 'banner_categories';

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

    public function banners(): BelongsToMany
    {
        return $this->belongsToMany(Banner::class, 'banner_category_banner')
            ->withPivot('sort_order')
            ->withTimestamps()
            ->orderBy('banner_category_banner.sort_order')
            ->orderBy('banners.title');
    }
}
