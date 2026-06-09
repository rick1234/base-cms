<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class BaseModuleConventionsController extends Controller
{
    public function index(): View
    {
        return view('admin.base-module-conventions.index', [
            'routeNames' => $this->routeNames(),
            'fakeRows' => $this->records(),
            'categories' => $this->categoryRows(),
        ]);
    }

    public function create(): View
    {
        return $this->edit(null);
    }

    public function edit(?int $id = null, ?string $tab = null): View
    {
        $record = $id ? $this->findRecord($id) : $this->emptyRecord();
        $activeTab = $record['id'] && in_array($tab, ['form', 'seo'], true) ? $tab : 'info';

        return view('admin.base-module-conventions.edit', [
            'activeTab' => $activeTab,
            'categories' => $this->categoryRows(),
            'fakeBlocks' => $this->blocks(),
            'fakeRecord' => $record,
            'layoutSets' => $this->layoutSets(),
            'routeNames' => $this->routeNames(),
        ]);
    }

    public function images(int $id): View
    {
        return view('admin.base-module-conventions.images', [
            'fakeRecord' => $this->findRecord($id),
            'routeNames' => $this->routeNames(),
        ]);
    }

    public function categories(): View
    {
        return view('admin.base-module-conventions.categories', [
            'categories' => $this->categoryRows(),
            'routeNames' => $this->routeNames(),
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function routeNames(): array
    {
        $prefix = request()->routeIs('cms.*') ? 'cms.base-module-conventions.' : 'admin.base-module-conventions.';

        return [
            'index' => $prefix.'index',
            'create' => $prefix.'create',
            'edit' => $prefix.'edit',
            'edit.tab' => $prefix.'edit.tab',
            'images' => $prefix.'images',
            'categories' => $prefix.'categories.index',
        ];
    }

    /**
     * @return array{id: null, title: string, slug: string, status: string, locale: string, category: string, createdBy: string, createdAt: string}
     */
    private function emptyRecord(): array
    {
        return [
            'id' => null,
            'title' => '',
            'slug' => '',
            'status' => 'draft',
            'locale' => 'nl',
            'category' => __('News'),
            'createdBy' => 'CMS Admin',
            'createdAt' => '08-06-2026 14:00',
        ];
    }

    /**
     * @return array{id: int|null, title: string, slug: string, status: string, locale: string, category: string, createdBy: string, createdAt: string}
     */
    private function findRecord(int $id): array
    {
        return collect($this->records())->firstWhere('id', $id) ?? $this->records()[0];
    }

    /**
     * @return array<int, array{id: int, title: string, slug: string, status: string, locale: string, category: string, createdBy: string, createdAt: string}>
     */
    private function records(): array
    {
        return [
            [
                'id' => 23,
                'title' => __('Reference content item'),
                'slug' => 'reference-content-item',
                'status' => 'published',
                'locale' => 'nl',
                'category' => __('News'),
                'createdBy' => 'CMS Admin',
                'createdAt' => '08-06-2026 14:00',
            ],
            [
                'id' => 24,
                'title' => __('Landing page draft'),
                'slug' => 'landing-page-draft',
                'status' => 'draft',
                'locale' => 'nl',
                'category' => __('Pages'),
                'createdBy' => 'CMS Admin',
                'createdAt' => '07-06-2026 11:30',
            ],
            [
                'id' => 25,
                'title' => __('Event detail page'),
                'slug' => 'event-detail-page',
                'status' => 'published',
                'locale' => 'en',
                'category' => __('Events'),
                'createdBy' => 'CMS Admin',
                'createdAt' => '06-06-2026 09:15',
            ],
        ];
    }

    /**
     * @return array<int, array{id: int, name: string, status: string, count: int}>
     */
    private function categoryRows(): array
    {
        return [
            ['id' => 1, 'name' => __('News'), 'status' => 'active', 'count' => 1],
            ['id' => 2, 'name' => __('Landing pages'), 'status' => 'active', 'count' => 1],
            ['id' => 3, 'name' => __('Events'), 'status' => 'active', 'count' => 1],
        ];
    }

    /**
     * @return array<int, array{key: string, label: string, source: string}>
     */
    private function layoutSets(): array
    {
        return [
            ['key' => 'admin.index.content', 'label' => __('Index layout'), 'source' => __('Content')],
            ['key' => 'admin.edit.content', 'label' => __('Edit layout'), 'source' => __('Content')],
            ['key' => 'admin.block-builder.content-events', 'label' => __('Block builder layout'), 'source' => __('Content and events')],
            ['key' => 'admin.builder.forms', 'label' => __('Form builder and submissions layout'), 'source' => __('Forms')],
            ['key' => 'admin.media.album', 'label' => __('Media album layout'), 'source' => __('Content, events, and locations')],
        ];
    }

    /**
     * @return array<int, array{icon: string, title: string, width: string}>
     */
    private function blocks(): array
    {
        return [
            ['icon' => 'title', 'title' => __('Title block'), 'width' => '100%'],
            ['icon' => 'subject', 'title' => __('Text block'), 'width' => '65%'],
            ['icon' => 'image', 'title' => __('Image block'), 'width' => '35%'],
        ];
    }
}
