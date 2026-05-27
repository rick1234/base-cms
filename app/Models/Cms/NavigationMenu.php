<?php

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NavigationMenu extends CmsModel
{
    protected $table = 'navigation_menus';

    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(NavigationMenuItem::class)->orderBy('sort_order')->orderBy('title');
    }

    public function rootItems(): HasMany
    {
        return $this->items()->whereNull('parent_id');
    }

    /**
     * @param  Builder<NavigationMenu>  $query
     * @return Builder<NavigationMenu>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @param  Builder<NavigationMenu>  $query
     * @return Builder<NavigationMenu>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}
