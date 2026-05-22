<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\PageIndexRequest;
use App\Http\Resources\Api\V1\PageResource;
use App\Models\Cms\Page;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PageController extends Controller
{
    public function index(PageIndexRequest $request): AnonymousResourceCollection
    {
        $status = $request->validated('status', 'published');

        $query = Page::query()->ordered();

        if ($status === 'published') {
            $query->published();
        } elseif ($status === 'draft') {
            $this->authorize('viewAny', Page::class);
            $query->where('status', 'draft');
        } elseif ($status === 'all') {
            $this->authorize('viewAny', Page::class);
        }

        return PageResource::collection($query->paginate(20));
    }

    public function show(Page $page): PageResource
    {
        abort_unless($page->isPublished(), 404);

        return PageResource::make($page);
    }
}
