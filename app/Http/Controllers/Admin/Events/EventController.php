<?php

namespace App\Http\Controllers\Admin\Events;

use App\Actions\Admin\Events\UpsertEvent;
use App\Http\Controllers\Admin\Concerns\UsesEditViewForCreate;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Events\EventActionRequest;
use App\Http\Requests\Admin\Events\EventMediaRequest;
use App\Http\Requests\Admin\Events\EventRequest;
use App\Models\Cms\Event;
use App\Models\Cms\EventCategory;
use App\Models\Cms\EventImage;
use App\Models\Cms\EventPart;
use App\Models\Cms\Form;
use App\Support\Admin\Events\EventMediaManager;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EventController extends Controller
{
    use UsesEditViewForCreate;

    /**
     * @var list<string>
     */
    private const EDIT_TABS = ['schedule', 'form', 'attachments', 'images', 'seo'];

    public function index(Request $request): View|RedirectResponse
    {
        if ($this->shouldMoveEvent($request)) {
            $this->moveEventWithinCategory($request->integer('categoryId'), $request->integer('move'), $request->string('direction')->toString());

            return redirect()->route($this->routeName('index'), $request->except(['move', 'direction']));
        }

        $categories = $this->categories();
        $categoryId = $request->integer('categoryId');
        $query = Event::query()
            ->with('categories')
            ->withCount(['attachments', 'images', 'parts']);

        if ($request->filled('id')) {
            $query->whereKey($request->integer('id'));
        }

        if ($request->filled('titel') || $request->filled('title')) {
            $query->where('title', 'like', '%'.$request->input('title', $request->input('titel')).'%');
        }

        if ($request->filled('status') && $this->normalizeStatus($request->input('status')) !== null) {
            $query->where('status', $this->normalizeStatus($request->input('status')));
        }

        if ($categoryId > 0) {
            $categoryIds = [$categoryId];

            if ($request->boolean('showChild')) {
                $categoryIds = [...$categoryIds, ...$this->descendantIds($categories, $categoryId)];
            }

            $query->whereHas('categories', fn ($categoryQuery) => $categoryQuery->whereIn('event_categories.id', $categoryIds));
        }

        $events = $query
            ->orderBy($this->sortColumn($request->string('sort')->toString()), $request->string('sorttype')->lower()->toString() === 'asc' ? 'asc' : 'desc')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view('admin.events.index', [
            'events' => $events,
            'categories' => $categories,
            'selectedCategoryId' => $categoryId,
            'showChild' => $request->boolean('showChild'),
            'routeNames' => $this->routeNames(),
        ]);
    }

    public function store(EventRequest $request, UpsertEvent $upsert): RedirectResponse
    {
        $event = $upsert->handle(
            $request->validated(),
            $request->user(),
            null,
            $this->uploadedFiles($request->file('attachment_files') ?: $request->file('attachment')),
            $request->file('image'),
        );

        flash(__('Event created.'))->success();

        return redirect()
            ->route($this->routeName('edit'), ['id' => $event->id]);
    }

    public function edit(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->redirectQueryTabToRoute($request)) {
            return $redirect;
        }

        $event = $this->eventFromRequest($request);
        $event?->load(['categories', 'attachments', 'images', 'parts']);
        $editableEvent = $event ?? new Event([
            'status' => 'draft',
            'locale' => app()->getLocale(),
            'active_from' => now(),
            'starts_at' => now(),
        ]);
        $activeTab = $this->activeTab($request, $event);

        return view('admin.events.edit', [
            'event' => $editableEvent,
            'activeTab' => $activeTab,
            'categories' => $this->categories(),
            'forms' => Form::query()->orderBy('name')->get(),
            'routeNames' => $this->routeNames(),
            'pageName' => __('Edit event'),
            'backUrl' => route($this->routeName('index')),
            'deleteAction' => $event ? route($this->routeName('destroy'), $event) : null,
        ]);
    }

    public function save(EventRequest $request, UpsertEvent $upsert): RedirectResponse
    {
        $event = $upsert->handle(
            $request->validated(),
            $request->user(),
            $request->event(),
            $this->uploadedFiles($request->file('attachment_files') ?: $request->file('attachment')),
            $request->file('image'),
        );

        flash(__('Event saved.'))->success();

        $activeTab = $request->validated('active_tab');

        return redirect()
            ->route($this->editRouteName($activeTab), $this->editRedirectParameters($event, $activeTab));
    }

    public function update(Event $event, EventRequest $request, UpsertEvent $upsert): RedirectResponse
    {
        $event = $upsert->handle(
            $request->validated(),
            $request->user(),
            $event,
            $this->uploadedFiles($request->file('attachment_files') ?: $request->file('attachment')),
            $request->file('image'),
        );

        flash(__('Event saved.'))->success();

        $activeTab = $request->validated('active_tab');

        return redirect()
            ->route($this->editRouteName($activeTab), $this->editRedirectParameters($event, $activeTab));
    }

    public function destroy(Event $event): RedirectResponse
    {
        $event->delete();

        flash(__('Event deleted.'))->success();

        return redirect()
            ->route($this->routeName('index'));
    }

    public function uploadImage(EventMediaRequest $request, EventMediaManager $mediaManager): JsonResponse|RedirectResponse
    {
        $event = Event::query()->findOrFail($request->integer('id'));
        $files = collect([$request->file('file'), $request->file('image')])
            ->merge($request->file('images', []))
            ->filter(fn (mixed $file): bool => $file instanceof UploadedFile)
            ->values();

        abort_unless($files->isNotEmpty(), 422);

        $images = $files->map(function (UploadedFile $file) use ($request, $event, $mediaManager): EventImage {
            $caption = $request->string('caption')->toString() ?: $this->defaultCaptionForUpload($file);

            return $mediaManager->storeImage($event, $file, $caption, $request->user(), [
                'alt_text' => $request->string('alt_text')->toString() ?: $caption,
                'title_text' => $request->string('title_text')->toString() ?: $caption,
                'description' => $request->string('description')->toString() ?: null,
                'credit' => $request->string('credit')->toString() ?: null,
                'is_decorative' => $request->boolean('is_decorative'),
            ]);
        });

        if (! $request->expectsJson()) {
            flash(trans_choice('{1} Image uploaded.|[2,*] Images uploaded.', $images->count()))->success();

            return back();
        }

        $image = $images->first();

        return response()->json([
            'jsonrpc' => '2.0',
            'status' => 'success',
            'result' => $image->id,
            'id' => $image->id,
            'count' => $images->count(),
        ]);
    }

    public function updateImageName(EventMediaRequest $request): JsonResponse|RedirectResponse
    {
        $image = EventImage::query()->findOrFail($request->integer('uploadId') ?: $request->integer('id'));
        $image->fill([
            'caption' => $request->input('uploadName', $request->input('caption')),
            'updated_by' => $request->user()?->id,
        ])->save();

        if (! $request->expectsJson()) {
            flash(__('Name updated.'))->success();

            return back();
        }

        return response()->json([
            'status' => 'success',
            'message' => __('Name updated.'),
        ]);
    }

    public function updateImageSort(EventMediaRequest $request, EventMediaManager $mediaManager): JsonResponse|RedirectResponse
    {
        $ids = collect(explode(',', (string) $request->input('sort_index')))
            ->map(fn (string $id): int => (int) trim($id))
            ->filter()
            ->values()
            ->all();

        $mediaManager->updateSortOrder(EventImage::class, $ids, $request->user());

        if (! $request->expectsJson()) {
            flash(__('Sort order updated.'))->success();

            return back();
        }

        return response()->json(['status' => 'success']);
    }

    public function deleteImage(EventMediaRequest $request, EventMediaManager $mediaManager): JsonResponse|RedirectResponse
    {
        $image = EventImage::query()->findOrFail($request->integer('id'));
        $mediaManager->deleteMedia($image, $request->user());

        if (! $request->expectsJson()) {
            flash(__('Image deleted.'))->success();

            return back();
        }

        return response()->json(['status' => 'success']);
    }

    public function deletePart(EventActionRequest $request): JsonResponse|RedirectResponse
    {
        $id = $request->integer('part_id')
            ?: $request->integer('event_part_id')
            ?: $request->integer('onderdeelId')
            ?: $request->integer('id');

        EventPart::query()->whereKey($id)->delete();

        if (! $request->expectsJson()) {
            flash(__('Event part deleted.'))->success();

            return back();
        }

        return response()->json(['status' => 'success']);
    }

    private function eventFromRequest(Request $request): ?Event
    {
        $id = (int) ($request->route('id') ?: $request->integer('id'));

        return $id > 0 ? Event::query()->findOrFail($id) : null;
    }

    private function activeTab(Request $request, ?Event $event): string
    {
        $tab = $request->route('tab') ?: $request->query('tab');

        if (! $event instanceof Event || ! is_string($tab) || ! in_array($tab, self::EDIT_TABS, true)) {
            return 'general';
        }

        return $tab;
    }

    /**
     * @return Collection<int, EventCategory>
     */
    private function categories(): Collection
    {
        return EventCategory::query()
            ->withCount('events')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * @param  Collection<int, EventCategory>  $categories
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
            'titel', 'title' => 'title',
            'startdatum', 'active_from' => 'active_from',
            'evenementstartdatum', 'starts_at' => 'starts_at',
            'status' => 'status',
            'sort_index', 'sort_order' => 'sort_order',
            default => 'id',
        };
    }

    private function normalizeStatus(mixed $status): ?string
    {
        return match ((string) $status) {
            '1', 'online', 'active', 'published' => 'published',
            '0', 'offline', 'inactive', 'draft' => 'draft',
            'archived' => 'archived',
            '' => null,
            default => is_string($status) ? $status : null,
        };
    }

    private function shouldMoveEvent(Request $request): bool
    {
        return $request->integer('categoryId') > 0
            && $request->integer('move') > 0
            && in_array($request->string('direction')->toString(), ['up', 'down'], true);
    }

    private function moveEventWithinCategory(int $categoryId, int $eventId, string $direction): void
    {
        $rows = DB::table('event_category_event')
            ->where('event_category_id', $categoryId)
            ->orderBy('sort_order')
            ->orderBy('event_id')
            ->get();

        $currentIndex = $rows->search(fn (object $row): bool => (int) $row->event_id === $eventId);

        if ($currentIndex === false) {
            return;
        }

        $targetIndex = $direction === 'up' ? $currentIndex - 1 : $currentIndex + 1;

        if (! isset($rows[$targetIndex])) {
            return;
        }

        $current = $rows[$currentIndex];
        $target = $rows[$targetIndex];

        DB::table('event_category_event')
            ->where('event_category_id', $categoryId)
            ->where('event_id', $current->event_id)
            ->update(['sort_order' => $target->sort_order]);

        DB::table('event_category_event')
            ->where('event_category_id', $categoryId)
            ->where('event_id', $target->event_id)
            ->update(['sort_order' => $current->sort_order]);
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
            'destroy' => $this->routeName('destroy'),
            'image.delete' => $this->routeName('image.delete'),
            'image.update-name' => $this->routeName('image.update-name'),
            'image.update-sort' => $this->routeName('image.update-sort'),
            'image.upload' => $this->routeName('image.upload'),
            'part.delete' => $this->routeName('part.delete'),
        ];
    }

    private function routeName(string $name): string
    {
        return (request()->routeIs('cms.*') ? 'cms.events.' : 'admin.events.').$name;
    }

    private function editRouteName(?string $activeTab = null): string
    {
        if (! request()->routeIs('cms.*') && in_array($activeTab, self::EDIT_TABS, true)) {
            return $this->routeName('edit.tab');
        }

        return $this->routeName('edit');
    }

    /**
     * @return array<string, mixed>
     */
    private function editRedirectParameters(Event $event, ?string $activeTab = null): array
    {
        $parameters = ['id' => $event->id];

        if (in_array($activeTab, self::EDIT_TABS, true)) {
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

    private function defaultCaptionForUpload(UploadedFile $file): string
    {
        return str(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME))
            ->replace(['-', '_'], ' ')
            ->squish()
            ->title()
            ->toString();
    }

    /**
     * @return array<int, UploadedFile>
     */
    private function uploadedFiles(mixed $files): array
    {
        if ($files instanceof UploadedFile) {
            return [$files];
        }

        if (! is_array($files)) {
            return [];
        }

        return array_values(array_filter($files, fn (mixed $file): bool => $file instanceof UploadedFile));
    }
}
