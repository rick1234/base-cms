<?php

namespace App\Http\Controllers\Admin\Translations;

use App\Actions\Admin\Translations\SyncTranslationKeys;
use App\Actions\Admin\Translations\UpsertTranslationKey;
use App\Http\Controllers\Admin\Concerns\UsesEditViewForCreate;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Translations\TranslationBulkUpdateRequest;
use App\Http\Requests\Admin\Translations\TranslationKeyRequest;
use App\Models\Cms\TranslationKey;
use App\Support\Localization\TranslationRepository;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TranslationController extends Controller
{
    use UsesEditViewForCreate;

    public function __construct(private readonly TranslationRepository $translations) {}

    public function index(Request $request): View
    {
        $languages = $this->translations->enabledLanguages();
        $selectedLocale = $this->translations->normalizeLocale(
            $request->string('locale')->toString() ?: $this->translations->fallbackLocale(),
        );

        if (! $languages->pluck('code')->contains($selectedLocale)) {
            $selectedLocale = $languages->first()?->code ?: $this->translations->defaultLocale();
        }

        return view('admin.translations.index', [
            'selectedLocale' => $selectedLocale,
            'editingId' => null,
            'create' => false,
            'editorTitle' => null,
        ]);
    }

    public function store(TranslationKeyRequest $request, UpsertTranslationKey $upsert): RedirectResponse
    {
        $translationKey = $upsert->handle($request->validated(), $request->user());

        flash(__('Translation key created.'))->success();

        return redirect()->route($this->routeName('edit'), ['id' => $translationKey->id]);
    }

    public function edit(Request $request): View
    {
        $translationKey = $this->translationKeyFromRequest($request);
        $selectedLocale = $this->translations->normalizeLocale(
            $request->string('locale')->toString() ?: $this->translations->fallbackLocale(),
        );

        return view('admin.translations.index', [
            'selectedLocale' => $selectedLocale,
            'editingId' => $translationKey?->id,
            'create' => $translationKey === null,
            'editorTitle' => $this->editorTitleFor($request, $translationKey),
        ]);
    }

    public function save(TranslationKeyRequest $request, UpsertTranslationKey $upsert): RedirectResponse
    {
        $translationKey = $upsert->handle($request->validated(), $request->user(), $request->translationKey());

        flash(__('Translation key saved.'))->success();

        return redirect()->route($this->routeName('edit'), ['id' => $translationKey->id]);
    }

    public function update(TranslationKey $translationKey, TranslationKeyRequest $request, UpsertTranslationKey $upsert): RedirectResponse
    {
        $translationKey = $upsert->handle($request->validated(), $request->user(), $translationKey);

        flash(__('Translation key saved.'))->success();

        return redirect()->route($this->routeName('edit'), ['id' => $translationKey->id]);
    }

    public function bulkUpdate(TranslationBulkUpdateRequest $request): RedirectResponse
    {
        foreach ($request->validated('translations', []) as $translationKeyId => $value) {
            $translationKey = TranslationKey::query()->find((int) $translationKeyId);

            if (! $translationKey) {
                continue;
            }

            $translationKey->values()->updateOrCreate(
                ['locale' => $request->validated('locale')],
                [
                    'value' => $value,
                    'status' => 'active',
                    'is_reviewed' => filled($value),
                    'reviewed_at' => filled($value) ? now() : null,
                    'updated_by' => $request->user()?->id,
                ],
            );
        }

        flash(__('Translations saved.'))->success();

        return back();
    }

    public function sync(SyncTranslationKeys $syncTranslationKeys): RedirectResponse
    {
        $result = $syncTranslationKeys->handle();

        flash(__('Translations synchronized: :created created, :updated updated.', [
            'created' => $result['created'],
            'updated' => $result['updated'],
        ]))->success();

        return back();
    }

    public function destroy(TranslationKey $translationKey): RedirectResponse
    {
        $translationKey->values()->delete();
        $translationKey->delete();

        flash(__('Translation key deleted.'))->success();

        return redirect()->route($this->routeName('index'));
    }

    private function translationKeyFromRequest(Request $request): ?TranslationKey
    {
        $id = (int) ($request->route('id') ?: $request->integer('id'));

        return $id > 0 ? TranslationKey::query()->findOrFail($id) : null;
    }

    private function editorTitleFor(Request $request, ?TranslationKey $translationKey): ?string
    {
        if ($translationKey) {
            return 'Edit translation';
        }

        $routeName = (string) $request->route()?->getName();

        return str_contains($routeName, 'edit')
            ? 'Edit translation'
            : 'Toevoegen';
    }

    /**
     * @return array<string, string>
     */
    private function areas(): array
    {
        return [
            'admin' => __('Backend'),
            'frontend' => __('Frontend'),
            'shared' => __('Shared'),
        ];
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
            'bulk' => $this->routeName('bulk'),
            'sync' => $this->routeName('sync'),
            'destroy' => $this->routeName('destroy'),
        ];
    }

    private function routeName(string $name): string
    {
        return (request()->routeIs('cms.*') ? 'cms.translations.' : 'admin.translations.').$name;
    }
}
