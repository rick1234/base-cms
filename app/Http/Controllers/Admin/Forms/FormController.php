<?php

namespace App\Http\Controllers\Admin\Forms;

use App\Actions\Admin\Forms\DuplicateForm;
use App\Actions\Admin\Forms\SaveFormStructure;
use App\Actions\Admin\Forms\UpsertForm;
use App\Http\Controllers\Admin\Concerns\UsesEditViewForCreate;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Forms\FormRequest as AdminFormRequest;
use App\Http\Requests\Admin\Forms\FormStructureRequest;
use App\Models\Cms\Form;
use App\Models\Cms\FormCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class FormController extends Controller
{
    use UsesEditViewForCreate;

    public function index(Request $request): View|RedirectResponse
    {
        if ($this->shouldMoveForm($request)) {
            $this->moveFormWithinCategory($request->integer('categoryId'), $request->integer('move') ?: $request->integer('id'), $request->string('direction')->toString());

            return redirect()->route($this->routeName('index'), $request->except(['move', 'direction']));
        }

        $categories = $this->categories();
        $categoryId = $request->integer('categoryId');
        $query = Form::query()
            ->with('categories')
            ->withCount(['blocks', 'submissions']);

        if ($request->filled('id')) {
            $query->whereKey($request->integer('id'));
        }

        if ($request->filled('name') || $request->filled('title')) {
            $query->where('name', 'like', '%'.$request->input('name', $request->input('title')).'%');
        }

        if ($request->filled('status') && $this->normalizeStatus($request->input('status')) !== null) {
            $query->where('status', $this->normalizeStatus($request->input('status')));
        }

        if ($categoryId > 0) {
            $categoryIds = [$categoryId];

            if ($request->boolean('showChild')) {
                $categoryIds = [...$categoryIds, ...$this->descendantIds($categories, $categoryId)];
            }

            $query->whereHas('categories', fn ($categoryQuery) => $categoryQuery->whereIn('form_categories.id', $categoryIds));
        }

        $forms = $query
            ->orderBy($this->sortColumn($request->string('sort')->toString()), $request->string('sorttype')->lower()->toString() === 'asc' ? 'asc' : 'desc')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view('admin.forms.index', [
            'forms' => $forms,
            'categories' => $categories,
            'selectedCategoryId' => $categoryId,
            'showChild' => $request->boolean('showChild'),
            'routeNames' => $this->routeNames(),
        ]);
    }

    public function store(AdminFormRequest $request, UpsertForm $upsert): RedirectResponse
    {
        $form = $upsert->handle($request->validated(), $request->user());

        flash(__('Form created.'))->success();

        return redirect()->route($this->routeName('edit'), ['id' => $form->id]);
    }

    public function edit(Request $request): View
    {
        $form = $this->formFromRequest($request);
        $form?->load([
            'categories',
            'recipients',
            'messages',
            'blocks.rows.fields.options',
        ])->loadCount('submissions');

        return view('admin.forms.edit', [
            'form' => $form ?? new Form([
                'status' => 'draft',
                'locale' => app()->getLocale(),
                'submit_text' => __('Versturen'),
                'success_message' => __('Thank you. Your message was sent.'),
                'settings' => [
                    'show_title' => true,
                    'layout' => 'default',
                    'mail_template' => 'forms.default',
                    'store_submissions' => true,
                    'honeypot_enabled' => true,
                    'honeypot_field' => 'website',
                ],
            ]),
            'categories' => $this->categories(),
            'fieldTypes' => Form::fieldTypes(),
            'routeNames' => $this->routeNames(),
            'pageName' => __('Edit form'),
            'backUrl' => route($this->routeName('index')),
            'deleteAction' => $form ? route($this->routeName('destroy'), $form) : null,
        ]);
    }

    public function save(AdminFormRequest $request, UpsertForm $upsert): RedirectResponse
    {
        $form = $upsert->handle($request->validated(), $request->user(), $request->formModel());

        flash(__('Form saved.'))->success();

        return redirect()->route($this->routeName('edit'), ['id' => $form->id]);
    }

    public function update(Form $form, AdminFormRequest $request, UpsertForm $upsert): RedirectResponse
    {
        $form = $upsert->handle($request->validated(), $request->user(), $form);

        flash(__('Form saved.'))->success();

        return redirect()->route($this->routeName('edit'), ['id' => $form->id]);
    }

    public function saveBuilder(FormStructureRequest $request, SaveFormStructure $saveStructure): RedirectResponse
    {
        $form = $saveStructure->handle($request->formModel(), $request->validated(), $request->user());

        flash(__('Form builder saved.'))->success();

        return redirect()->route($this->routeName('edit'), ['id' => $form->id, 'tab' => 'builder']);
    }

    public function submissions(Request $request): View
    {
        $form = $this->formFromRequest($request);

        if (! $form) {
            return view('admin.forms.submissions', [
                'form' => null,
                'submissions' => collect(),
                'routeNames' => $this->routeNames(),
                'pageName' => __('Form messages'),
                'backUrl' => route($this->routeName('index')),
            ]);
        }

        $form->load('blocks.rows.fields');

        $submissions = $form->submissions()
            ->with('answers')
            ->paginate(25)
            ->withQueryString();

        return view('admin.forms.submissions', [
            'form' => $form,
            'submissions' => $submissions,
            'routeNames' => $this->routeNames(),
            'pageName' => __('Form messages'),
            'backUrl' => route($this->routeName('edit'), ['id' => $form->id]),
        ]);
    }

    public function destroy(Form $form): RedirectResponse
    {
        $form->delete();

        flash(__('Form deleted.'))->success();

        return redirect()->route($this->routeName('index'));
    }

    public function duplicate(Request $request, DuplicateForm $duplicate): JsonResponse|RedirectResponse
    {
        $id = $request->integer('itemId') ?: $request->integer('form_id') ?: $request->integer('id');
        $form = Form::query()
            ->with(['categories', 'recipients', 'messages', 'blocks.rows.fields.options'])
            ->findOrFail($id);

        $copy = $duplicate->handle($form, $request->user());

        if (! $request->expectsJson()) {
            flash(__('Form duplicated.'))->success();

            return redirect()->route($this->routeName('edit'), ['id' => $copy->id]);
        }

        return response()->json([
            'status' => 'success',
            'id' => $copy->id,
            'edit_url' => route($this->routeName('edit'), ['id' => $copy->id]),
        ]);
    }

    private function formFromRequest(Request $request): ?Form
    {
        $id = (int) ($request->route('id') ?: $request->integer('id'));

        return $id > 0 ? Form::query()->findOrFail($id) : null;
    }

    /**
     * @return Collection<int, FormCategory>
     */
    private function categories(): Collection
    {
        return FormCategory::query()
            ->withCount('forms')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * @param  Collection<int, FormCategory>  $categories
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
            'name', 'title', 'naam' => 'name',
            'status' => 'status',
            'sort_index', 'sort_order' => 'sort_order',
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

    private function shouldMoveForm(Request $request): bool
    {
        return $request->integer('categoryId') > 0
            && ($request->integer('move') > 0 || $request->integer('id') > 0)
            && in_array($request->string('direction')->toString(), ['up', 'down'], true);
    }

    private function moveFormWithinCategory(int $categoryId, int $formId, string $direction): void
    {
        $rows = DB::table('form_category_form')
            ->where('form_category_id', $categoryId)
            ->orderBy('sort_order')
            ->orderBy('form_id')
            ->get();

        $currentIndex = $rows->search(fn (object $row): bool => (int) $row->form_id === $formId);

        if ($currentIndex === false) {
            return;
        }

        $targetIndex = $direction === 'up' ? $currentIndex - 1 : $currentIndex + 1;

        if (! isset($rows[$targetIndex])) {
            return;
        }

        $current = $rows[$currentIndex];
        $target = $rows[$targetIndex];

        DB::table('form_category_form')
            ->where('form_category_id', $categoryId)
            ->where('form_id', $current->form_id)
            ->update(['sort_order' => $target->sort_order]);

        DB::table('form_category_form')
            ->where('form_category_id', $categoryId)
            ->where('form_id', $target->form_id)
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
            'save' => $this->routeName('save'),
            'builder.save' => $this->routeName('builder.save'),
            'submissions' => $this->routeName('submissions'),
            'destroy' => $this->routeName('destroy'),
            'duplicate' => $this->routeName('duplicate'),
        ];
    }

    private function routeName(string $name): string
    {
        return (request()->routeIs('cms.*') ? 'cms.forms.' : 'admin.forms.').$name;
    }
}
