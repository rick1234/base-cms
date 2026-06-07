<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Cms\Vacancy;
use App\Models\Cms\VacancyCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class VacancyController extends Controller
{
    public function index(Request $request): View
    {
        $activeCategorySlug = trim((string) $request->query('category'));
        $activeCategory = $activeCategorySlug === ''
            ? null
            : VacancyCategory::query()
                ->where('slug', $activeCategorySlug)
                ->where('status', 'active')
                ->first();

        $vacancies = Vacancy::query()
            ->published()
            ->with('categories')
            ->where(function (Builder $query): void {
                $query->whereNull('locale')
                    ->orWhere('locale', app()->getLocale());
            })
            ->when($activeCategory instanceof VacancyCategory, function (Builder $query) use ($activeCategory): void {
                $query->whereHas('categories', fn (Builder $query): Builder => $query->whereKey($activeCategory->id));
            })
            ->orderBy('sort_order')
            ->orderBy('title')
            ->paginate(12)
            ->withQueryString();

        $categories = VacancyCategory::query()
            ->where('status', 'active')
            ->where('is_hidden_from_navigation', false)
            ->whereHas('vacancies', function (Builder $query): void {
                $query->published()
                    ->where(function (Builder $query): void {
                        $query->whereNull('locale')
                            ->orWhere('locale', app()->getLocale());
                    });
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('frontend.vacancies.index', [
            'page' => [
                'title' => __('Vacancies'),
                'meta_description' => __('Explore current vacancies.'),
            ],
            'vacancies' => $vacancies,
            'categories' => $categories,
            'activeCategory' => $activeCategory,
        ]);
    }

    public function show(Request $request): View
    {
        $vacancyKey = (string) $request->route('vacancy');

        $vacancy = Vacancy::query()
            ->published()
            ->with(['attachments', 'categories', 'form.blocks.rows.fields.options'])
            ->where(function (Builder $query) use ($vacancyKey): void {
                $query->where('slug', $vacancyKey);

                if (ctype_digit($vacancyKey)) {
                    $query->orWhereKey((int) $vacancyKey);
                }
            })
            ->where(function (Builder $query): void {
                $query->whereNull('locale')
                    ->orWhere('locale', app()->getLocale());
            })
            ->firstOrFail();

        return view('frontend.vacancies.show', [
            'page' => $vacancy,
            'vacancy' => $vacancy,
        ]);
    }
}
