<?php

namespace App\Http\Controllers\Admin\Locations;

use App\Actions\Admin\Locations\DuplicateLocation;
use App\Actions\Admin\Locations\SaveLocationOpeningHours;
use App\Actions\Admin\Locations\UpsertLocation;
use App\Http\Controllers\Admin\Concerns\UsesEditViewForCreate;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Locations\LocationMediaRequest;
use App\Http\Requests\Admin\Locations\LocationOpeningHoursRequest;
use App\Http\Requests\Admin\Locations\LocationRequest;
use App\Models\Cms\Location;
use App\Models\Cms\LocationCategory;
use App\Models\Cms\LocationImage;
use App\Models\Cms\LocationOpeningHour;
use App\Models\Cms\LocationSpecialOpeningHour;
use App\Support\Admin\Locations\LocationMediaManager;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class LocationController extends Controller
{
    use UsesEditViewForCreate;

    /**
     * @var list<string>
     */
    private const EDIT_TABS = ['location'];

    public function index(Request $request): View|RedirectResponse
    {
        if ($this->shouldMoveLocation($request)) {
            $this->moveLocationWithinCategory($request->integer('categoryId'), $request->integer('move') ?: $request->integer('id'), $request->string('direction')->toString());

            return redirect()->route($this->routeName('index'), $request->except(['move', 'direction']));
        }

        $categories = $this->categories();
        $categoryId = $request->integer('categoryId');
        $query = Location::query()
            ->with('categories')
            ->withCount(['images', 'openingHours', 'specialOpeningHours']);

        if ($request->filled('id')) {
            $query->whereKey($request->integer('id'));
        }

        if ($request->filled('name') || $request->filled('naam')) {
            $query->where('name', 'like', '%'.$request->input('name', $request->input('naam')).'%');
        }

        if ($request->filled('city') || $request->filled('plaats')) {
            $query->where('city', 'like', '%'.$request->input('city', $request->input('plaats')).'%');
        }

        if ($request->filled('status') && $this->normalizeStatus($request->input('status')) !== null) {
            $query->where('status', $this->normalizeStatus($request->input('status')));
        }

        if ($categoryId > 0) {
            $categoryIds = [$categoryId];

            if ($request->boolean('showChild')) {
                $categoryIds = [...$categoryIds, ...$this->descendantIds($categories, $categoryId)];
            }

            $query->whereHas('categories', fn ($categoryQuery) => $categoryQuery->whereIn('location_categories.id', $categoryIds));
        }

        $locations = $query
            ->orderBy($this->sortColumn($request->string('sort')->toString()), $request->string('sorttype')->lower()->toString() === 'desc' ? 'desc' : 'asc')
            ->orderBy('id')
            ->paginate(25)
            ->withQueryString();

        return view('admin.locations.index', [
            'locations' => $locations,
            'categories' => $categories,
            'selectedCategoryId' => $categoryId,
            'showChild' => $request->boolean('showChild'),
            'routeNames' => $this->routeNames(),
        ]);
    }

    public function store(LocationRequest $request, UpsertLocation $upsert): RedirectResponse
    {
        $location = $upsert->handle($request->validated(), $request->user());

        flash(__('Location created.'))->success();

        return redirect()->route($this->routeName('edit'), ['id' => $location->id]);
    }

    public function edit(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->redirectQueryTabToRoute($request)) {
            return $redirect;
        }

        $location = $this->locationFromRequest($request);
        $location?->load(['categories', 'images', 'openingHours', 'specialOpeningHours']);
        $editableLocation = $location ?? new Location([
            'status' => 'active',
            'active_from' => now(),
            'country_code' => 'NL',
            'metadata' => [],
        ]);

        return view('admin.locations.edit', [
            'location' => $editableLocation,
            'activeTab' => $this->activeTab($request, $location),
            'categories' => $this->categories(),
            'routeNames' => $this->routeNames(),
            'pageName' => __('Edit location'),
            'backUrl' => route($this->routeName('index')),
            'deleteAction' => $location ? route($this->routeName('destroy'), $location) : null,
        ]);
    }

    public function save(LocationRequest $request, UpsertLocation $upsert): RedirectResponse
    {
        $location = $upsert->handle($request->validated(), $request->user(), $request->location());

        flash(__('Location saved.'))->success();

        $activeTab = $request->validated('active_tab');

        return redirect()
            ->route($this->editRouteName($activeTab), $this->editRedirectParameters($location, $activeTab));
    }

    public function update(Location $location, LocationRequest $request, UpsertLocation $upsert): RedirectResponse
    {
        $location = $upsert->handle($request->validated(), $request->user(), $location);

        flash(__('Location saved.'))->success();

        $activeTab = $request->validated('active_tab');

        return redirect()
            ->route($this->editRouteName($activeTab), $this->editRedirectParameters($location, $activeTab));
    }

    public function destroy(Location $location): RedirectResponse
    {
        $location->delete();

        flash(__('Location deleted.'))->success();

        return redirect()->route($this->routeName('index'));
    }

    public function duplicate(Request $request, DuplicateLocation $duplicate): JsonResponse|RedirectResponse
    {
        $location = Location::query()
            ->with(['categories', 'images', 'openingHours', 'specialOpeningHours'])
            ->findOrFail($request->integer('itemId') ?: $request->integer('id'));

        $copy = $duplicate->handle($location, $request->user());

        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'success',
                'id' => $copy->id,
                'edit_url' => route($this->routeName('edit'), ['id' => $copy->id]),
            ]);
        }

        flash(__('Location duplicated.'))->success();

        return redirect()->route($this->routeName('edit'), ['id' => $copy->id]);
    }

    public function images(Request $request): View
    {
        $location = $this->locationForUtility($request);

        if (! $location) {
            return $this->utilityPlaceholder(__('Location images'));
        }

        $location->load('images');

        return view('admin.locations.images', [
            'location' => $location,
            'routeNames' => $this->routeNames(),
            'pageName' => __('Location images'),
            'backUrl' => route($this->routeName('edit'), ['id' => $location->id]),
        ]);
    }

    public function uploadImage(LocationMediaRequest $request, LocationMediaManager $mediaManager): JsonResponse|RedirectResponse
    {
        $location = Location::query()->findOrFail($request->integer('location_id') ?: $request->integer('id'));
        $files = collect([$request->file('file'), $request->file('image'), $request->file('afbeelding')])
            ->merge($request->file('images', []))
            ->filter(fn (mixed $file): bool => $file instanceof UploadedFile)
            ->values();

        abort_unless($files->isNotEmpty(), 422);

        $images = $files->map(function (UploadedFile $file) use ($request, $location, $mediaManager): LocationImage {
            $caption = $request->string('caption')->toString() ?: $this->defaultCaptionForUpload($file);

            return $mediaManager->storeImage($location, $file, $caption, $request->user(), [
                'alt_text' => $request->string('alt_text')->toString() ?: $caption,
                'title_text' => $request->string('title_text')->toString() ?: $caption,
                'description' => $request->string('description')->toString() ?: null,
                'credit' => $request->string('credit')->toString() ?: null,
                'is_decorative' => $request->boolean('is_decorative'),
            ]);
        });

        if ($request->expectsJson()) {
            $image = $images->first();

            return response()->json([
                'jsonrpc' => '2.0',
                'status' => 'success',
                'result' => $image->id,
                'id' => $image->id,
                'count' => $images->count(),
            ]);
        }

        flash(trans_choice('{1} Image uploaded.|[2,*] Images uploaded.', $images->count()))->success();

        return redirect()->route($this->routeName('images'), ['id' => $location->id]);
    }

    public function updateImageName(LocationMediaRequest $request): JsonResponse|RedirectResponse
    {
        $image = LocationImage::query()->findOrFail($request->integer('uploadId') ?: $request->integer('id'));
        $image->fill([
            'caption' => $request->input('uploadName', $request->input('caption')),
            'updated_by' => $request->user()?->id,
        ])->save();

        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => __('Name updated.'),
            ]);
        }

        flash(__('Name updated.'))->success();

        return back();
    }

    public function updateImageSort(LocationMediaRequest $request, LocationMediaManager $mediaManager): JsonResponse|RedirectResponse
    {
        $ids = collect(explode(',', (string) $request->input('sort_index')))
            ->map(fn (string $id): int => (int) trim($id))
            ->filter()
            ->values()
            ->all();

        $mediaManager->updateSortOrder(LocationImage::class, $ids, $request->user());

        if ($request->expectsJson()) {
            return response()->json(['status' => 'success']);
        }

        flash(__('Sort order updated.'))->success();

        return back();
    }

    public function deleteImage(LocationMediaRequest $request, LocationMediaManager $mediaManager): JsonResponse|RedirectResponse
    {
        $image = LocationImage::query()->findOrFail($request->integer('uploadId') ?: $request->integer('id'));
        $mediaManager->deleteMedia($image, $request->user());

        if ($request->expectsJson()) {
            return response()->json(['status' => 'success']);
        }

        flash(__('Image deleted.'))->success();

        return back();
    }

    public function openingHours(Request $request): View
    {
        $location = $this->locationForUtility($request);

        if (! $location) {
            return $this->utilityPlaceholder(__('Location opening hours'));
        }

        $location->load(['openingHours', 'specialOpeningHours']);

        return view('admin.locations.opening-hours', [
            'location' => $location,
            'openingHours' => $location->openingHours->keyBy('day'),
            'dayNames' => Location::dayNames(),
            'routeNames' => $this->routeNames(),
            'pageName' => __('Location opening hours'),
            'backUrl' => route($this->routeName('edit'), ['id' => $location->id]),
        ]);
    }

    public function saveOpeningHours(LocationOpeningHoursRequest $request, SaveLocationOpeningHours $save): RedirectResponse
    {
        $location = $save->handle($request->location(), $request->validated(), $request->user());

        flash(__('Opening hours saved.'))->success();

        return redirect()->route($this->routeName('opening-hours'), ['id' => $location->id]);
    }

    public function deleteOpeningHour(Request $request): JsonResponse|RedirectResponse
    {
        $id = $request->integer('openingHourId') ?: $request->integer('opening_time_id') ?: $request->integer('id');
        $specialOpeningHour = LocationSpecialOpeningHour::query()->find($id);
        $openingHour = $specialOpeningHour ? null : LocationOpeningHour::query()->findOrFail($id);

        $specialOpeningHour?->delete();
        $openingHour?->delete();

        if ($request->expectsJson()) {
            return response()->json(['status' => 'success']);
        }

        flash(__('Opening hour deleted.'))->success();

        return back();
    }

    private function locationFromRequest(Request $request): ?Location
    {
        $id = (int) ($request->route('id') ?: $request->integer('id'));

        return $id > 0 ? Location::query()->findOrFail($id) : null;
    }

    private function locationForUtility(Request $request): ?Location
    {
        $id = (int) ($request->route('id') ?: $request->integer('id') ?: $request->integer('location_id'));

        return $id > 0 ? Location::query()->findOrFail($id) : null;
    }

    /**
     * @return Collection<int, LocationCategory>
     */
    private function categories(): Collection
    {
        return LocationCategory::query()
            ->withCount('locations')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * @param  Collection<int, LocationCategory>  $categories
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
            'plaats', 'city' => 'city',
            'status' => 'status',
            'startdatum', 'active_from' => 'active_from',
            'einddatum', 'active_until' => 'active_until',
            'sort_index', 'sort_order' => 'sort_order',
            default => 'id',
        };
    }

    private function normalizeStatus(mixed $status): ?string
    {
        return match ((string) $status) {
            '1', 'online', 'active', 'published' => 'active',
            '0', '2', '3', 'offline', 'inactive', 'draft' => 'inactive',
            '' => null,
            default => is_string($status) ? $status : null,
        };
    }

    private function shouldMoveLocation(Request $request): bool
    {
        return $request->integer('categoryId') > 0
            && ($request->integer('move') > 0 || $request->integer('id') > 0)
            && in_array($request->string('direction')->toString(), ['up', 'down'], true);
    }

    private function moveLocationWithinCategory(int $categoryId, int $locationId, string $direction): void
    {
        $rows = DB::table('location_category_location')
            ->where('location_category_id', $categoryId)
            ->orderBy('sort_order')
            ->orderBy('location_id')
            ->get();

        $currentIndex = $rows->search(fn (object $row): bool => (int) $row->location_id === $locationId);

        if ($currentIndex === false) {
            return;
        }

        $targetIndex = $direction === 'up' ? $currentIndex - 1 : $currentIndex + 1;

        if (! isset($rows[$targetIndex])) {
            return;
        }

        $current = $rows[$currentIndex];
        $target = $rows[$targetIndex];

        DB::table('location_category_location')
            ->where('location_category_id', $categoryId)
            ->where('location_id', $current->location_id)
            ->update(['sort_order' => $target->sort_order]);

        DB::table('location_category_location')
            ->where('location_category_id', $categoryId)
            ->where('location_id', $target->location_id)
            ->update(['sort_order' => $current->sort_order]);
    }

    private function utilityPlaceholder(string $pageName): View
    {
        return view('admin.locations.utility', [
            'pageName' => $pageName,
            'routeNames' => $this->routeNames(),
            'backUrl' => route($this->routeName('index')),
        ]);
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
            'edit.tab' => $this->routeName('edit.tab'),
            'save' => $this->routeName('save'),
            'destroy' => $this->routeName('destroy'),
            'duplicate' => $this->routeName('duplicate'),
            'images' => $this->routeName('images'),
            'image.delete' => $this->routeName('image.delete'),
            'image.update-name' => $this->routeName('image.update-name'),
            'image.update-sort' => $this->routeName('image.update-sort'),
            'image.upload' => $this->routeName('image.upload'),
            'opening-hours' => $this->routeName('opening-hours'),
            'opening-hours.save' => $this->routeName('opening-hours.save'),
            'opening-hour.delete' => $this->routeName('opening-hour.delete'),
        ];
    }

    private function routeName(string $name): string
    {
        return (request()->routeIs('cms.*') ? 'cms.locations.' : 'admin.locations.').$name;
    }

    private function activeTab(Request $request, ?Location $location): string
    {
        $tab = (string) $request->route('tab');

        if ($location && in_array($tab, self::EDIT_TABS, true)) {
            return $tab;
        }

        return 'general';
    }

    private function editRouteName(?string $activeTab = null): string
    {
        if (in_array($activeTab, self::EDIT_TABS, true)) {
            return $this->routeName('edit.tab');
        }

        return $this->routeName('edit');
    }

    /**
     * @return array<string, mixed>
     */
    private function editRedirectParameters(Location $location, ?string $activeTab = null): array
    {
        $parameters = ['id' => $location->id];

        if (in_array($activeTab, self::EDIT_TABS, true)) {
            $parameters['tab'] = $activeTab;
        }

        return $parameters;
    }

    private function redirectQueryTabToRoute(Request $request): ?RedirectResponse
    {
        if ($request->route('tab')) {
            return null;
        }

        $tab = $request->query('tab');

        if (! is_string($tab) || ! in_array($tab, self::EDIT_TABS, true)) {
            return null;
        }

        $location = $this->locationFromRequest($request);

        if (! $location) {
            return null;
        }

        return redirect()
            ->route($this->editRouteName($tab), $this->editRedirectParameters($location, $tab));
    }

    private function defaultCaptionForUpload(UploadedFile $file): string
    {
        return str(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME))
            ->replace(['-', '_'], ' ')
            ->squish()
            ->title()
            ->toString();
    }
}
