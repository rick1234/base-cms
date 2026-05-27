<?php

namespace App\Http\Controllers\Admin\Localization;

use App\Http\Controllers\Admin\Concerns\UsesEditViewForCreate;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Localization\LanguageRequest;
use App\Http\Requests\Admin\Localization\LanguageSettingsRequest;
use App\Models\Cms\CmsLanguage;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LanguageController extends Controller
{
    use UsesEditViewForCreate;

    public function index(): View
    {
        $languages = CmsLanguage::query()
            ->orderByDesc('is_enabled')
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();

        return view('admin.localization.languages.index', [
            'languages' => $languages,
            'enabledLanguages' => $languages->where('is_enabled', true)->values(),
            'defaultLanguage' => $languages->firstWhere('is_default', true),
            'routeNames' => $this->routeNames(),
        ]);
    }

    public function updateSettings(LanguageSettingsRequest $request): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($data): void {
            CmsLanguage::query()->update([
                'is_enabled' => false,
                'is_default' => false,
            ]);

            CmsLanguage::query()
                ->whereIn('id', $data['enabled_languages'])
                ->update([
                    'status' => 'active',
                    'is_enabled' => true,
                ]);

            CmsLanguage::query()
                ->whereKey($data['default_language'])
                ->update([
                    'status' => 'active',
                    'is_enabled' => true,
                    'is_default' => true,
                ]);
        });

        flash(__('Website languages saved.'))->success();

        return redirect()->route($this->routeName('index'));
    }

    public function edit(Request $request): View
    {
        $language = $this->languageFromRequest($request);

        return view('admin.localization.languages.edit', [
            'language' => $language ?? new CmsLanguage([
                'status' => 'active',
                'direction' => 'ltr',
                'is_enabled' => true,
            ]),
            'routeNames' => $this->routeNames(),
            'pageName' => __('Edit language'),
            'backUrl' => route($this->routeName('index')),
        ]);
    }

    public function save(LanguageRequest $request): RedirectResponse
    {
        $language = $this->persist($request->language() ?? new CmsLanguage, $request);

        flash(__('Language saved.'))->success();

        return redirect()->route($this->routeName('edit'), ['id' => $language->id]);
    }

    public function update(CmsLanguage $language, LanguageRequest $request): RedirectResponse
    {
        $this->persist($language, $request);

        flash(__('Language saved.'))->success();

        return redirect()->route($this->routeName('edit'), ['id' => $language->id]);
    }

    private function persist(CmsLanguage $language, LanguageRequest $request): CmsLanguage
    {
        $data = $request->validated();
        unset($data['id']);

        DB::transaction(function () use ($language, $data, $request): void {
            if (! $language->exists) {
                $language->created_by = $request->user()?->id;
            }

            if ($data['is_default']) {
                CmsLanguage::query()
                    ->when($language->exists, fn ($query) => $query->whereKeyNot($language->id))
                    ->update(['is_default' => false]);
            }

            $language->fill($data);
            $language->updated_by = $request->user()?->id;
            $language->save();

            $this->ensureDefaultLanguage($language);
        });

        return $language;
    }

    private function ensureDefaultLanguage(CmsLanguage $language): void
    {
        if (CmsLanguage::query()->where('is_default', true)->exists()) {
            return;
        }

        $language->forceFill([
            'status' => 'active',
            'is_enabled' => true,
            'is_default' => true,
        ])->save();
    }

    private function languageFromRequest(Request $request): ?CmsLanguage
    {
        $id = (int) ($request->route('id') ?: $request->integer('id'));

        return $id > 0 ? CmsLanguage::query()->findOrFail($id) : null;
    }

    /**
     * @return array<string, string>
     */
    private function routeNames(): array
    {
        if (request()->routeIs('cms.*')) {
            return [
                'index' => 'cms.countries.languages.index',
                'save-settings' => 'cms.countries.languages.save-settings',
                'create' => 'cms.countries.languages.edit',
                'edit' => 'cms.countries.languages.edit',
                'save' => 'cms.countries.languages.save',
                'update' => 'cms.countries.languages.update',
                'countries' => 'cms.countries.index',
            ];
        }

        return [
            'index' => 'admin.countries.languages.index',
            'save-settings' => 'admin.countries.languages.save-settings',
            'create' => 'admin.countries.languages.create',
            'edit' => 'admin.countries.languages.edit',
            'save' => 'admin.countries.languages.save',
            'update' => 'admin.countries.languages.update',
            'countries' => 'admin.countries.index',
        ];
    }

    private function routeName(string $name): string
    {
        return $this->routeNames()[$name];
    }
}
