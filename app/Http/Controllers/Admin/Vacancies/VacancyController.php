<?php

namespace App\Http\Controllers\Admin\Vacancies;

use App\Actions\Admin\Vacancies\UpsertVacancy;
use App\Http\Controllers\Admin\Concerns\UsesEditViewForCreate;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Vacancies\VacancyRequest;
use App\Models\Cms\Form;
use App\Models\Cms\Vacancy;
use App\Models\Cms\VacancyCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class VacancyController extends Controller
{
    use UsesEditViewForCreate;

    /**
     * @var list<string>
     */
    private const EDIT_TABS = ['seo', 'form'];

    public function index(Request $request): View
    {
        $categories = $this->categories();
        $categoryId = $request->integer('categoryId');
        $query = Vacancy::query()
            ->with('categories')
            ->with(['form' => fn ($formQuery) => $formQuery->withCount('submissions')]);

        if ($request->filled('id')) {
            $query->whereKey($request->integer('id'));
        }

        if ($request->filled('title') || $request->filled('titel') || $request->filled('naam')) {
            $query->where('title', 'like', '%'.$request->input('title', $request->input('titel', $request->input('naam'))).'%');
        }

        if ($request->filled('locale') || $request->filled('taalcode')) {
            $query->where('locale', $request->input('locale', $request->input('taalcode')));
        }

        if ($request->filled('status') && $this->normalizeStatus($request->input('status')) !== null) {
            $query->where('status', $this->normalizeStatus($request->input('status')));
        }

        if ($categoryId > 0) {
            $categoryIds = [$categoryId];

            if ($request->boolean('showChild')) {
                $categoryIds = [...$categoryIds, ...$this->descendantIds($categories, $categoryId)];
            }

            $query->whereHas('categories', fn ($categoryQuery) => $categoryQuery->whereIn('vacancy_categories.id', $categoryIds));
        }

        $vacancies = $query
            ->orderBy($this->sortColumn($request->string('sort')->toString()), $request->string('sorttype')->lower()->toString() === 'asc' ? 'asc' : 'desc')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view('admin.vacancies.index', [
            'vacancies' => $vacancies,
            'categories' => $categories,
            'selectedCategoryId' => $categoryId,
            'showChild' => $request->boolean('showChild'),
            'routeNames' => $this->routeNames(),
        ]);
    }

    public function store(VacancyRequest $request, UpsertVacancy $upsert): RedirectResponse
    {
        $vacancy = $upsert->handle($request->validated(), $request->user());

        flash(__('Vacancy created.'))->success();

        return redirect()->route($this->routeName('edit'), ['id' => $vacancy->id]);
    }

    public function edit(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->redirectQueryTabToRoute($request)) {
            return $redirect;
        }

        $vacancy = $this->vacancyFromRequest($request);
        $vacancy?->load([
            'categories',
            'form' => fn ($formQuery) => $formQuery->withCount('submissions'),
        ]);

        return view('admin.vacancies.edit', [
            'vacancy' => $vacancy ?? new Vacancy([
                'status' => 'draft',
                'locale' => app()->getLocale(),
                'active_from' => now(),
            ]),
            'categories' => $this->categories(),
            'forms' => Form::query()->withCount('submissions')->orderBy('name')->get(),
            'activeTab' => $this->activeTab($request),
            'routeNames' => $this->routeNames(),
            'pageName' => __('Edit vacancy'),
            'backUrl' => route($this->routeName('index')),
            'deleteAction' => $vacancy ? route($this->routeName('destroy'), $vacancy) : null,
        ]);
    }

    public function save(VacancyRequest $request, UpsertVacancy $upsert): RedirectResponse
    {
        $vacancy = $upsert->handle($request->validated(), $request->user(), $request->vacancy());

        flash(__('Vacancy saved.'))->success();

        $activeTab = $request->validated('active_tab');

        return redirect()->route($this->editRouteName($activeTab), $this->editRedirectParameters($vacancy, $activeTab));
    }

    public function update(Vacancy $vacancy, VacancyRequest $request, UpsertVacancy $upsert): RedirectResponse
    {
        $vacancy = $upsert->handle($request->validated(), $request->user(), $vacancy);

        flash(__('Vacancy saved.'))->success();

        $activeTab = $request->validated('active_tab');

        return redirect()->route($this->editRouteName($activeTab), $this->editRedirectParameters($vacancy, $activeTab));
    }

    public function destroy(Vacancy $vacancy): RedirectResponse
    {
        $vacancy->delete();

        flash(__('Vacancy deleted.'))->success();

        return redirect()->route($this->routeName('index'));
    }

    private function vacancyFromRequest(Request $request): ?Vacancy
    {
        $id = (int) ($request->route('id') ?: $request->integer('id'));

        return $id > 0 ? Vacancy::query()->findOrFail($id) : null;
    }

    /**
     * @return Collection<int, VacancyCategory>
     */
    private function categories(): Collection
    {
        return VacancyCategory::query()
            ->withCount('vacancies')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * @param  Collection<int, VacancyCategory>  $categories
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
            'title', 'titel', 'naam' => 'title',
            'locale', 'taal' => 'locale',
            'startdatum', 'active_from' => 'active_from',
            'einddatum', 'active_until' => 'active_until',
            'status' => 'status',
            default => 'id',
        };
    }

    private function normalizeStatus(mixed $status): ?string
    {
        return match ((string) $status) {
            '1', 'online', 'active', 'published' => 'published',
            '0', '2', '3', 'offline', 'inactive', 'draft' => 'draft',
            'archived' => 'archived',
            '' => null,
            default => is_string($status) ? $status : null,
        };
    }

    private function activeTab(Request $request): string
    {
        $tab = (string) ($request->route('tab') ?: $request->query('tab'));

        return in_array($tab, self::EDIT_TABS, true) ? $tab : 'info';
    }

    private function redirectQueryTabToRoute(Request $request): ?RedirectResponse
    {
        $tab = (string) $request->query('tab');

        if ($tab === '' || ! in_array($tab, self::EDIT_TABS, true)) {
            return null;
        }

        $id = (int) ($request->route('id') ?: $request->integer('id'));

        return $id > 0
            ? redirect()->route($this->routeName('edit.tab'), ['id' => $id, 'tab' => $tab])
            : null;
    }

    private function editRouteName(?string $activeTab): string
    {
        return in_array($activeTab, self::EDIT_TABS, true)
            ? $this->routeName('edit.tab')
            : $this->routeName('edit');
    }

    /**
     * @return array<string, mixed>
     */
    private function editRedirectParameters(Vacancy $vacancy, ?string $activeTab): array
    {
        $parameters = ['id' => $vacancy->id];

        if (in_array($activeTab, self::EDIT_TABS, true)) {
            $parameters['tab'] = $activeTab;
        }

        return $parameters;
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
            'update' => $this->routeName('update'),
            'destroy' => $this->routeName('destroy'),
        ];
    }

    private function routeName(string $name): string
    {
        return (request()->routeIs('cms.*') ? 'cms.vacancies.' : 'admin.vacancies.').$name;
    }
}
