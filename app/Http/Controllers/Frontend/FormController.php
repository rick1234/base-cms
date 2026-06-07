<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Cms\Domain;
use App\Models\Cms\Form;
use App\Models\Cms\FormCategory;
use App\Support\Domains\DomainResolver;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class FormController extends Controller
{
    public function index(Request $request): View
    {
        $domain = app()->bound(DomainResolver::CONTAINER_KEY)
            ? app(DomainResolver::CONTAINER_KEY)
            : null;
        $activeCategorySlug = trim((string) $request->query('category'));
        $activeCategory = $activeCategorySlug === ''
            ? null
            : FormCategory::query()
                ->where('slug', $activeCategorySlug)
                ->where('status', 'active')
                ->first();

        $forms = Form::query()
            ->published()
            ->with('categories')
            ->where(function (Builder $query): void {
                $query->whereNull('locale')
                    ->orWhere('locale', app()->getLocale());
            })
            ->when($domain instanceof Domain, function (Builder $query) use ($domain): void {
                $query->where(function (Builder $query) use ($domain): void {
                    $query->where('domain_id', $domain->id)
                        ->orWhereNull('domain_id');
                });
            })
            ->when($activeCategory instanceof FormCategory, function (Builder $query) use ($activeCategory): void {
                $query->whereHas('categories', fn (Builder $query): Builder => $query->whereKey($activeCategory->id));
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $categories = FormCategory::query()
            ->where('status', 'active')
            ->where('is_hidden_from_navigation', false)
            ->whereHas('forms', function (Builder $query) use ($domain): void {
                $query->published()
                    ->where(function (Builder $query): void {
                        $query->whereNull('locale')
                            ->orWhere('locale', app()->getLocale());
                    })
                    ->when($domain instanceof Domain, function (Builder $query) use ($domain): void {
                        $query->where(function (Builder $query) use ($domain): void {
                            $query->where('domain_id', $domain->id)
                                ->orWhereNull('domain_id');
                        });
                    });
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('frontend.forms.index', [
            'page' => [
                'title' => __('Forms'),
                'meta_description' => __('Find the right form for your request.'),
            ],
            'forms' => $this->preferDomainRecords($forms, $domain),
            'categories' => $categories,
            'activeCategory' => $activeCategory,
        ]);
    }

    public function show(Request $request): View
    {
        $form = $this->publicForm((string) $request->route('form'));

        abort_unless($form instanceof Form, 404);

        $form->load(['blocks.rows.fields.options', 'categories']);

        return view('frontend.forms.show', [
            'form' => $form,
            'page' => [
                'title' => $form->name,
                'meta_description' => $form->description ?: __('Submit this form.'),
            ],
        ]);
    }

    public function publicForm(string $slug): ?Form
    {
        $domain = app()->bound(DomainResolver::CONTAINER_KEY)
            ? app(DomainResolver::CONTAINER_KEY)
            : null;

        $query = Form::query()
            ->published()
            ->where('slug', $slug)
            ->where(function (Builder $query): void {
                $query->whereNull('locale')
                    ->orWhere('locale', app()->getLocale());
            });

        return $this->firstForDomain($query, $domain);
    }

    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @return TModel|null
     */
    private function firstForDomain(Builder $query, mixed $domain): ?Model
    {
        if (! $domain instanceof Domain) {
            return $query->first();
        }

        return (clone $query)->where('domain_id', $domain->id)->first()
            ?: (clone $query)->whereNull('domain_id')->first();
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Form>  $forms
     * @return \Illuminate\Support\Collection<int, Form>
     */
    private function preferDomainRecords($forms, mixed $domain)
    {
        if (! $domain instanceof Domain) {
            return $forms->values();
        }

        return $forms
            ->sortByDesc(fn (Form $form): bool => (int) $form->getAttribute('domain_id') === $domain->id)
            ->unique(fn (Form $form): string => (string) $form->slug)
            ->values();
    }
}
