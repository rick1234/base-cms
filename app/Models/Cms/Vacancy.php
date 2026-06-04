<?php

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vacancy extends CmsModel
{
    use SoftDeletes;

    protected $table = 'vacancies';

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(VacancyCategory::class, 'vacancy_category_vacancy')
            ->withPivot('sort_order')
            ->withTimestamps()
            ->orderBy('vacancy_category_vacancy.sort_order')
            ->orderBy('vacancy_categories.name');
    }

    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(VacancyAttachment::class, 'vacancy_id')->orderBy('sort_order')->orderBy('id');
    }

    /**
     * @param  Builder<Vacancy>  $query
     * @return Builder<Vacancy>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('status', 'published')
            ->where(function (Builder $query): void {
                $query->whereNull('active_from')->orWhereDate('active_from', '<=', now());
            })
            ->where(function (Builder $query): void {
                $query->whereNull('active_until')->orWhereDate('active_until', '>=', now());
            });
    }

    public function isActive(): bool
    {
        if ($this->status !== 'published') {
            return false;
        }

        if ($this->active_from && $this->active_from->isFuture()) {
            return false;
        }

        if ($this->active_until && $this->active_until->isPast()) {
            return false;
        }

        return true;
    }

    public function publicRouteKey(): string
    {
        return $this->slug ?: (string) $this->id;
    }
}
