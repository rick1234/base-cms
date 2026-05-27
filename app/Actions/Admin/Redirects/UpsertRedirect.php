<?php

namespace App\Actions\Admin\Redirects;

use App\Models\Cms\CmsRedirect;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Arr;

class UpsertRedirect
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data, ?Authenticatable $actor = null, ?CmsRedirect $redirect = null): CmsRedirect
    {
        $redirect ??= new CmsRedirect;
        $attributes = Arr::only($data, [
            'source_path',
            'target_url',
            'description',
            'status_code',
            'is_active',
            'preserve_query',
        ]);

        if (! $redirect->exists) {
            $attributes['created_by'] = $actor?->getAuthIdentifier();
        }

        $attributes['updated_by'] = $actor?->getAuthIdentifier();

        $redirect->fill($attributes)->save();

        return $redirect->refresh();
    }
}
