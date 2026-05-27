<?php

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class WebsiteTemplate extends CmsModel
{
    use SoftDeletes;

    protected $table = 'website_templates';

    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'default_settings' => 'array',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function domains(): HasMany
    {
        return $this->hasMany(Domain::class, 'website_template_id');
    }

    /**
     * @return array<string, mixed>
     */
    public function settingsWithDefaults(): array
    {
        return [
            ...config('cms_domains.default_template_settings', []),
            ...($this->default_settings ?? []),
        ];
    }
}
