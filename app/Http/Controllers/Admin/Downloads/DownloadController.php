<?php

namespace App\Http\Controllers\Admin\Downloads;

use App\Actions\Admin\Downloads\UpsertDownload;
use App\Http\Controllers\Admin\Concerns\UsesEditViewForCreate;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Downloads\DownloadFileRequest;
use App\Http\Requests\Admin\Downloads\DownloadRequest;
use App\Models\Cms\Download;
use App\Models\Cms\DownloadCategory;
use App\Support\Admin\Downloads\DownloadFileManager;
use App\Support\Downloads\DownloadLinkIssuer;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;

class DownloadController extends Controller
{
    use UsesEditViewForCreate;

    public function index(Request $request): View
    {
        $categories = $this->categories();
        $categoryId = $request->integer('categoryId');
        $query = Download::query()
            ->with('categories')
            ->withCount('accessTokens');

        if ($request->filled('id')) {
            $query->whereKey($request->integer('id'));
        }

        if ($request->filled('name') || $request->filled('naam')) {
            $query->where('name', 'like', '%'.$request->input('name', $request->input('naam')).'%');
        }

        if ($request->filled('status') || $request->filled('actief')) {
            $query->where('status', $this->normalizeStatus($request->input('status', $request->input('actief'))));
        }

        if ($categoryId > 0) {
            $categoryIds = [$categoryId];

            if ($request->boolean('showChild')) {
                $categoryIds = [...$categoryIds, ...$this->descendantIds($categories, $categoryId)];
            }

            $query->whereHas('categories', fn ($categoryQuery) => $categoryQuery->whereIn('download_categories.id', $categoryIds));
        }

        $downloads = $query
            ->orderBy($this->sortColumn($request->string('sort')->toString()), $request->string('sorttype')->lower()->toString() === 'asc' ? 'asc' : 'desc')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view('admin.downloads.index', [
            'downloads' => $downloads,
            'categories' => $categories,
            'selectedCategoryId' => $categoryId,
            'showChild' => $request->boolean('showChild'),
            'routeNames' => $this->routeNames(),
        ]);
    }

    public function store(DownloadRequest $request, UpsertDownload $upsert): RedirectResponse
    {
        $download = $upsert->handle(
            $request->validated(),
            $request->user(),
            null,
            $this->uploadedFile($request),
        );

        flash(__('Download created.'))->success();

        return redirect()->route($this->routeName('edit'), ['id' => $download->id]);
    }

    public function edit(Request $request): View
    {
        $download = $this->downloadFromRequest($request);
        $download?->load('categories');

        return view('admin.downloads.edit', [
            'download' => $download ?? new Download([
                'status' => 'active',
                'active_from' => now(),
                'file_disk' => 'local',
                'link_expires_after_minutes' => 60,
            ]),
            'categories' => $this->categories(),
            'routeNames' => $this->routeNames(),
            'pageName' => __('Edit download'),
            'backUrl' => route($this->routeName('index')),
            'deleteAction' => $download ? route($this->routeName('destroy'), $download) : null,
        ]);
    }

    public function save(DownloadRequest $request, UpsertDownload $upsert): RedirectResponse
    {
        $download = $upsert->handle(
            $request->validated(),
            $request->user(),
            $request->download(),
            $this->uploadedFile($request),
        );

        flash(__('Download saved.'))->success();

        return redirect()->route($this->routeName('edit'), ['id' => $download->id]);
    }

    public function update(Download $download, DownloadRequest $request, UpsertDownload $upsert): RedirectResponse
    {
        $download = $upsert->handle(
            $request->validated(),
            $request->user(),
            $download,
            $this->uploadedFile($request),
        );

        flash(__('Download saved.'))->success();

        return redirect()->route($this->routeName('edit'), ['id' => $download->id]);
    }

    public function destroy(Download $download): RedirectResponse
    {
        $download->delete();

        flash(__('Download deleted.'))->success();

        return redirect()->route($this->routeName('index'));
    }

    public function deleteFile(DownloadFileRequest $request, DownloadFileManager $fileManager): JsonResponse|RedirectResponse
    {
        $download = Download::query()->findOrFail($request->integer('id'));
        $fileManager->deleteFile($download);

        if ($request->expectsJson()) {
            return response()->json(['status' => 'success']);
        }

        flash(__('Download file deleted.'))->success();

        return back();
    }

    public function generateLink(Request $request, DownloadLinkIssuer $issuer): JsonResponse|RedirectResponse
    {
        $download = Download::query()->findOrFail($request->integer('id'));
        abort_unless($download->hasFile(), 404);

        $url = $issuer->issue($download);

        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'success',
                'url' => $url,
            ]);
        }

        flash(__('Unique download URL generated: :url', ['url' => $url]))->success();

        return back();
    }

    private function downloadFromRequest(Request $request): ?Download
    {
        $id = (int) ($request->route('id') ?: $request->integer('id'));

        return $id > 0 ? Download::query()->findOrFail($id) : null;
    }

    /**
     * @return Collection<int, DownloadCategory>
     */
    private function categories(): Collection
    {
        return DownloadCategory::query()
            ->withCount('downloads')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * @param  Collection<int, DownloadCategory>  $categories
     * @return list<int>
     */
    private function descendantIds(Collection $categories, int $parentId): array
    {
        $ids = [];

        foreach ($categories->where('parent_id', $parentId) as $category) {
            $ids[] = $category->id;
            $ids = [...$ids, ...$this->descendantIds($categories, $category->id)];
        }

        return $ids;
    }

    private function sortColumn(string $sort): string
    {
        return match ($sort) {
            'naam', 'name' => 'name',
            'downloads', 'download_count', 'aantaldownloads' => 'download_count',
            'startdatum', 'active_from' => 'active_from',
            'einddatum', 'active_until' => 'active_until',
            'status' => 'status',
            default => 'id',
        };
    }

    private function normalizeStatus(mixed $status): string
    {
        return match ((string) $status) {
            '1', 'active', 'published' => 'active',
            '0', '2', '3', 'inactive', 'draft' => 'inactive',
            default => 'active',
        };
    }

    private function uploadedFile(DownloadRequest $request): ?UploadedFile
    {
        $file = $request->file('file') ?: $request->file('bestand');

        return $file instanceof UploadedFile ? $file : null;
    }

    /**
     * @return array<string, string>
     */
    private function routeNames(): array
    {
        return [
            'index' => $this->routeName('index'),
            'store' => $this->routeName('store'),
            'create' => request()->routeIs('cms.*') ? $this->routeName('edit') : $this->routeName('create'),
            'edit' => $this->routeName('edit'),
            'save' => $this->routeName('save'),
            'update' => $this->routeName('update'),
            'destroy' => $this->routeName('destroy'),
            'file.delete' => $this->routeName('file.delete'),
            'link.generate' => $this->routeName('link.generate'),
        ];
    }

    private function routeName(string $name): string
    {
        return (request()->routeIs('cms.*') ? 'cms.downloads.' : 'admin.downloads.').$name;
    }
}
