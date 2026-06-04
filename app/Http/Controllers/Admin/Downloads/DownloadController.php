<?php

namespace App\Http\Controllers\Admin\Downloads;

use App\Actions\Admin\Downloads\UpsertDownload;
use App\Http\Controllers\Admin\Concerns\UsesEditViewForCreate;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Downloads\DownloadFileRequest;
use App\Http\Requests\Admin\Downloads\DownloadInviteRequest;
use App\Http\Requests\Admin\Downloads\DownloadRequest;
use App\Mail\DownloadInviteMail;
use App\Models\Cms\Download;
use App\Models\Cms\DownloadCategory;
use App\Support\Admin\Downloads\DownloadFileManager;
use App\Support\Admin\Downloads\DownloadStorageSecurityCheck;
use App\Support\Downloads\DownloadLinkIssuer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;

class DownloadController extends Controller
{
    use UsesEditViewForCreate;

    /**
     * @var list<string>
     */
    private const EDIT_TABS = ['storage', 'invites', 'log', 'qr'];

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

    public function edit(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->redirectQueryTabToRoute($request)) {
            return $redirect;
        }

        $download = $this->downloadFromRequest($request);
        $download?->load(['categories', 'accessTokens' => fn ($query) => $query->latest()]);
        $activeTab = $this->activeTab($request, $download);

        if ($activeTab === 'storage') {
            abort_unless($request->user()?->can('access-superuser'), 403);
        }

        $editableDownload = $download ?? new Download([
            'status' => 'active',
            'active_from' => now(),
            'file_disk' => 'local',
            'link_expires_after_minutes' => 60,
        ]);

        return view('admin.downloads.edit', [
            'download' => $editableDownload,
            'activeTab' => $activeTab,
            'categories' => $this->categories(),
            'accessTokens' => $download?->accessTokens ?? collect(),
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

        return redirect()->route($this->editRouteName($request->input('active_tab')), $this->editRedirectParameters($download, $request->input('active_tab')));
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

        return redirect()->route($this->editRouteName($request->input('active_tab')), $this->editRedirectParameters($download, $request->input('active_tab')));
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

        $url = $issuer->issue($download, createdBy: $request->user()?->id);

        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'success',
                'url' => $url,
            ]);
        }

        flash(__('Unique download URL generated: :url', ['url' => $url]))->success();

        return back();
    }

    public function sendInvites(Download $download, DownloadInviteRequest $request, DownloadLinkIssuer $issuer): RedirectResponse
    {
        foreach ($request->emails() as $email) {
            $url = $issuer->issue($download, $email, 'invite', $request->user()?->id);

            Mail::to($email)->send(new DownloadInviteMail($download, $url, $request->messageText()));
        }

        flash(trans_choice('{1} Download invite sent.|[2,*] Download invites sent.', count($request->emails())))->success();

        return redirect()->route($this->editRouteName('invites'), $this->editRedirectParameters($download, 'invites'));
    }

    public function testStorage(Download $download, Request $request, DownloadStorageSecurityCheck $check): RedirectResponse
    {
        abort_unless($request->user()?->can('access-superuser'), 403);

        $result = $check->run($download);
        $storageTestSessionKey = 'download_storage_test_result';
        session()->flash($storageTestSessionKey, $result);

        if ($result['passed']) {
            flash(__('Download storage test passed.'))->success();
        } else {
            flash(__('Download storage test found issues.'))->error();
        }

        return redirect()->route($this->editRouteName('storage'), $this->editRedirectParameters($download, 'storage'));
    }

    public function qr(Download $download): Response
    {
        abort_unless($download->hasFile(), 404);

        $renderer = new ImageRenderer(
            new RendererStyle(360),
            new SvgImageBackEnd,
        );
        $writer = new Writer($renderer);
        $svg = $writer->writeString(route('frontend.downloads.show', ['download' => $download->publicRouteKey()]));

        return response($svg, 200, [
            'Content-Type' => 'image/svg+xml',
            'Cache-Control' => 'private, max-age=300',
        ]);
    }

    private function downloadFromRequest(Request $request): ?Download
    {
        $id = (int) ($request->route('id') ?: $request->integer('id'));

        return $id > 0 ? Download::query()->findOrFail($id) : null;
    }

    private function activeTab(Request $request, ?Download $download): string
    {
        $tab = $request->route('tab') ?: $request->query('tab');

        if (! $download instanceof Download || ! is_string($tab) || ! in_array($tab, self::EDIT_TABS, true)) {
            return 'general';
        }

        return $tab;
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
            'edit.tab' => request()->routeIs('cms.*') ? $this->routeName('edit') : $this->routeName('edit.tab'),
            'save' => $this->routeName('save'),
            'update' => $this->routeName('update'),
            'destroy' => $this->routeName('destroy'),
            'file.delete' => $this->routeName('file.delete'),
            'link.generate' => $this->routeName('link.generate'),
            'invites.send' => $this->routeName('invites.send'),
            'storage.test' => $this->routeName('storage.test'),
            'qr.svg' => $this->routeName('qr.svg'),
        ];
    }

    private function routeName(string $name): string
    {
        return (request()->routeIs('cms.*') ? 'cms.downloads.' : 'admin.downloads.').$name;
    }

    private function editRouteName(mixed $activeTab = null): string
    {
        if (! request()->routeIs('cms.*') && is_string($activeTab) && in_array($activeTab, self::EDIT_TABS, true)) {
            return $this->routeName('edit.tab');
        }

        return $this->routeName('edit');
    }

    /**
     * @return array<string, mixed>
     */
    private function editRedirectParameters(Download $download, mixed $activeTab = null): array
    {
        $parameters = ['id' => $download->id];

        if (is_string($activeTab) && in_array($activeTab, self::EDIT_TABS, true)) {
            $parameters['tab'] = $activeTab;
        }

        return $parameters;
    }

    private function redirectQueryTabToRoute(Request $request): ?RedirectResponse
    {
        if (request()->routeIs('cms.*') || $request->route('tab')) {
            return null;
        }

        $tab = $request->query('tab');

        if (! is_string($tab) || ! in_array($tab, self::EDIT_TABS, true)) {
            return null;
        }

        $id = (int) ($request->route('id') ?: $request->integer('id'));

        if ($id <= 0) {
            return null;
        }

        return redirect()->route($this->routeName('edit.tab'), [
            'id' => $id,
            'tab' => $tab,
        ]);
    }
}
