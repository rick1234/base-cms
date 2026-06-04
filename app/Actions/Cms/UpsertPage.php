<?php

namespace App\Actions\Cms;

use App\Models\Cms\Page;
use App\Models\User;
use App\Support\Routing\PublicSlugManager;
use Illuminate\Support\Arr;

class UpsertPage
{
    public function __construct(private readonly PublicSlugManager $slugs) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data, User $user, ?Page $page = null): Page
    {
        $page ??= new Page;
        $creating = ! $page->exists;
        $oldSlug = $page->slug;

        $payload = Arr::only($data, [
            'domain_id',
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

        $payload['slug'] = $this->slugs->pageSlug($data['slug'] ?? null, $data['title'], $page);
        $payload['updated_by'] = $user->id;

        if ($creating) {
            $payload['created_by'] = $user->id;
        }

        $page->fill($payload);
        $page->save();

        if (! $creating) {
            $this->slugs->recordPageSlugChange($page, $oldSlug, $user);
        }

        return $page;
    }
}
