<?php

namespace App\Actions\Admin\Catalog;

use App\Models\Cms\CmsModel;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class UpsertCatalogRecord
{
    /**
     * @param  class-string<CmsModel>  $modelClass
     * @param  array<string, mixed>  $data
     */
    public function handle(string $modelClass, array $data, ?Authenticatable $actor = null, ?CmsModel $record = null): CmsModel
    {
        $record ??= new $modelClass;
        $attributes = Arr::only($data, [
            'name',
            'slug',
            'description',
            'website_url',
            'logo_path',
            'intro',
            'body',
            'meta_title',
            'meta_description',
            'status',
            'sort_order',
        ]);

        if (blank($attributes['slug'] ?? null) && filled($attributes['name'] ?? null)) {
            $attributes['slug'] = Str::slug((string) $attributes['name']);
        }

        if (! $record->exists) {
            $attributes['created_by'] = $actor?->getAuthIdentifier();
        }

        $attributes['updated_by'] = $actor?->getAuthIdentifier();

        $record->fill($attributes)->save();

        return $record;
    }
}
