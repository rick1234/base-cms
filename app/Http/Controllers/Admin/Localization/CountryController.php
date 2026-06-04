<?php

namespace App\Http\Controllers\Admin\Localization;

use App\Http\Controllers\Admin\Concerns\UsesEditViewForCreate;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Localization\CountryRequest;
use App\Models\Cms\Country;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CountryController extends Controller
{
    use UsesEditViewForCreate;

    public function index(Request $request): View
    {
        $query = Country::query();

        if ($request->filled('id')) {
            $query->whereKey($request->integer('id'));
        }

        if ($request->filled('name') || $request->filled('naam')) {
            $query->where('name', 'like', '%'.$request->input('name', $request->input('naam')).'%');
        }

        if ($request->filled('iso2') || $request->filled('code')) {
            $query->where('iso2', 'like', '%'.strtoupper((string) $request->input('iso2', $request->input('code'))).'%');
        }

        if ($request->filled('status') || $request->filled('active')) {
            $query->where('status', $this->normalizeStatus($request->input('status', $request->input('active'))));
        }

        if ($request->filled('enabled')) {
            $query->where('is_enabled', $request->boolean('enabled'));
        }

        $countries = $query
            ->orderBy($this->sortColumn($request->string('sort')->toString()), $request->string('sorttype')->lower()->toString() === 'desc' ? 'desc' : 'asc')
            ->orderBy('name')
            ->paginate(50)
            ->withQueryString();

        return view('admin.localization.countries.index', [
            'countries' => $countries,
            'routeNames' => $this->routeNames(),
        ]);
    }

    public function store(CountryRequest $request): RedirectResponse
    {
        $country = $this->persist(new Country, $request);

        flash(__('Country created.'))->success();

        return redirect()->route($this->routeName('edit'), ['id' => $country->id]);
    }

    public function edit(Request $request): View
    {
        $country = $this->countryFromRequest($request);

        return view('admin.localization.countries.edit', [
            'country' => $country ?? new Country([
                'status' => 'active',
                'is_enabled' => true,
                'charges_vat' => false,
            ]),
            'regionOptions' => $this->regionOptions(),
            'routeNames' => $this->routeNames(),
            'pageName' => __('Edit country'),
            'backUrl' => route($this->routeName('index')),
            'deleteAction' => $country ? route($this->routeName('destroy'), $country) : null,
        ]);
    }

    public function save(CountryRequest $request): RedirectResponse
    {
        $country = $this->persist($request->country() ?? new Country, $request);

        flash(__('Country saved.'))->success();

        return redirect()->route($this->routeName('edit'), ['id' => $country->id]);
    }

    public function update(Country $country, CountryRequest $request): RedirectResponse
    {
        $this->persist($country, $request);

        flash(__('Country saved.'))->success();

        return redirect()->route($this->routeName('edit'), ['id' => $country->id]);
    }

    public function destroy(Country $country): RedirectResponse
    {
        $country->delete();

        flash(__('Country deleted.'))->success();

        return redirect()->route($this->routeName('index'));
    }

    private function persist(Country $country, CountryRequest $request): Country
    {
        $data = $request->validated();
        unset($data['id']);

        if (! $country->exists) {
            $country->created_by = $request->user()?->id;
        }

        $country->fill($data);
        $country->updated_by = $request->user()?->id;
        $country->save();

        return $country;
    }

    private function countryFromRequest(Request $request): ?Country
    {
        $id = (int) ($request->route('id') ?: $request->integer('id'));

        return $id > 0 ? Country::query()->findOrFail($id) : null;
    }

    private function sortColumn(string $sort): string
    {
        return match ($sort) {
            'naam', 'name' => 'name',
            'code', 'iso2' => 'iso2',
            'currency', 'currency_code' => 'currency_code',
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

    /**
     * @return array<string, string>
     */
    private function regionOptions(): array
    {
        return [
            'EU' => __('Europe'),
            'AF' => __('Africa'),
            'AN' => __('Antarctica'),
            'AS' => __('Asia'),
            'NA' => __('North America'),
            'SA' => __('South America'),
            'OC' => __('Oceania'),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function routeNames(): array
    {
        if (request()->routeIs('cms.*')) {
            return [
                'index' => 'cms.countries.index',
                'store' => 'cms.countries.store',
                'create' => 'cms.countries.edit',
                'edit' => 'cms.countries.edit',
                'save' => 'cms.countries.save',
                'destroy' => 'cms.countries.destroy',
                'languages' => 'cms.countries.languages.index',
            ];
        }

        return [
            'index' => 'admin.countries.index',
            'store' => 'admin.countries.store',
            'create' => 'admin.countries.create',
            'edit' => 'admin.countries.edit',
            'save' => 'admin.countries.save',
            'destroy' => 'admin.countries.destroy',
            'languages' => 'admin.countries.languages.index',
        ];
    }

    private function routeName(string $name): string
    {
        return $this->routeNames()[$name];
    }
}
