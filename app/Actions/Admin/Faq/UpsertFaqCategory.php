<?php

namespace App\Actions\Admin\Faq;

use App\Models\Cms\FaqCategory;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class UpsertFaqCategory
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data, ?Authenticatable $actor = null, ?FaqCategory $category = null): FaqCategory
    {
        $category ??= new FaqCategory;
        $attributes = Arr::only($data, [
            'parent_id',
            'name',
            'slug',
            'description',
            'status',
            'sort_order',
        ]);

        if (blank($attributes['slug'] ?? null)) {
            $attributes['slug'] = Str::slug((string) $attributes['name']);
        }

        if (blank($attributes['parent_id'] ?? null) || (int) $attributes['parent_id'] === (int) $category->id) {
            $attributes['parent_id'] = null;
        }

        if (! $category->exists) {
            $attributes['created_by'] = $actor?->getAuthIdentifier();
            $attributes['sort_order'] ??= FaqCategory::query()
                ->where('parent_id', $attributes['parent_id'])
                ->max('sort_order') + 1;
        }

        $attributes['updated_by'] = $actor?->getAuthIdentifier();

        $category->fill($attributes)->save();

        return $category;
    }
}
