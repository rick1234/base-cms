<?php

namespace App\Actions\Cms;

use App\Models\Cms\Page;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class UpsertPage
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data, User $user, ?Page $page = null): Page
    {
        $page ??= new Page;

        $payload = Arr::only($data, [
            'parent_id',
            'title',
            'navigation_label',
            'excerpt',
            'body',
            'meta_title',
            'meta_description',
            'og_title',
            'og_description',
            'og_image',
            'canonical_url',
            'template',
            'status',
            'sort_order',
            'published_at',
        ]);

        $payload['slug'] = Str::slug($data['slug'] ?: $data['title']);
        $payload['updated_by'] = $user->id;

        if (! $page->exists) {
            $payload['created_by'] = $user->id;
        }

        $page->fill($payload);
        $page->save();

        return $page;
    }
}
