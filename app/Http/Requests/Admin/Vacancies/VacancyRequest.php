<?php

namespace App\Http\Requests\Admin\Vacancies;

use App\Models\Cms\Vacancy;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class VacancyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('access-admin') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $activeTab = $this->string('active_tab')->toString() ?: 'info';
        $data = [
            'active_tab' => $activeTab,
            'title' => $this->input('title', $this->input('titel', $this->input('naam'))),
            'slug' => $this->normalizedSlug($this->input('slug')),
            'locale' => $this->input('locale', $this->input('taalcode')),
            'body' => $this->input('body', $this->input('content', $this->input('tekst'))),
            'meta_description' => $this->input('meta_description', $this->input('metadescription')),
            'meta_title' => $this->input('meta_title'),
            'form_id' => $this->filled('form_id') ? $this->integer('form_id') : null,
            'status' => $this->normalizeStatus($this->input('status', $this->input('actief'))),
            'active_from' => $this->normalizeDate($this->input('active_from', $this->input('startdatum'))),
            'active_until' => $this->normalizeDate($this->input('active_until', $this->input('einddatum'))),
            'categories' => $this->input('categories', $this->input('categorie', [])),
            'location' => $this->input('location', $this->input('locatie')),
            'vacancy_type' => $this->input('vacancy_type', $this->input('type')),
            'employment_type' => $this->input('employment_type', $this->input('dienstverband')),
            'education_level' => $this->input('education_level', $this->input('opleidingsniveau')),
            'experience_level' => $this->input('experience_level', $this->input('ervaringsniveau')),
            'work_mode' => $this->input('work_mode', $this->input('werkwijze')),
            'hours' => $this->input('hours', $this->input('uren')),
            'salary' => $this->input('salary', $this->input('salaris')),
            'volunteer_commitment' => $this->input('volunteer_commitment', $this->input('vrijwilligers_inzet')),
            'volunteer_compensation' => $this->input('volunteer_compensation', $this->input('vrijwilligers_vergoeding')),
            'contact_email' => $this->input('contact_email', $this->input('email')),
        ];

        if ($vacancy = $this->vacancy()) {
            $data = $this->preserveExistingTabValues($data, $vacancy, $activeTab);
        }

        $this->merge($data);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'id' => ['nullable', 'integer', 'exists:vacancies,id'],
            'title' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                'alpha_dash',
            ],
            'locale' => ['nullable', 'string', 'max:8'],
            'body' => ['nullable', 'string'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'form_id' => ['nullable', 'integer', 'exists:forms,id'],
            'status' => ['required', Rule::in(['draft', 'published', 'archived'])],
            'active_from' => ['nullable', 'date'],
            'active_until' => ['nullable', 'date', 'after_or_equal:active_from'],
            'categories' => ['nullable', 'array'],
            'categories.*' => ['integer', 'exists:vacancy_categories,id'],
            'location' => ['nullable', 'string', 'max:255'],
            'vacancy_type' => ['nullable', Rule::in(['paid', 'volunteer'])],
            'employment_type' => ['nullable', 'string', 'max:255'],
            'education_level' => ['nullable', 'string', 'max:255'],
            'experience_level' => ['nullable', 'string', 'max:255'],
            'work_mode' => ['nullable', Rule::in(['on-site', 'hybrid', 'remote'])],
            'hours' => ['nullable', 'string', 'max:255'],
            'salary' => ['nullable', 'string', 'max:255'],
            'volunteer_commitment' => ['nullable', 'string', 'max:255'],
            'volunteer_compensation' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'active_tab' => ['sometimes', Rule::in(['info', 'seo', 'form'])],
            'saveAndStay' => ['sometimes', 'boolean'],
        ];
    }

    public function vacancy(): ?Vacancy
    {
        $routeVacancy = $this->route('vacancy');

        if ($routeVacancy instanceof Vacancy) {
            return $routeVacancy;
        }

        $id = (int) ($this->route('id') ?: $this->integer('id'));

        return $id > 0 ? Vacancy::query()->find($id) : null;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function preserveExistingTabValues(array $data, Vacancy $vacancy, string $activeTab): array
    {
        $metadata = (array) ($vacancy->metadata ?? []);
        $preserved = [
            'title' => $vacancy->title,
            'slug' => $vacancy->slug,
            'locale' => $vacancy->locale,
            'body' => $vacancy->body,
            'form_id' => $vacancy->form_id,
            'status' => $vacancy->status,
            'active_from' => optional($vacancy->active_from)->format('Y-m-d'),
            'active_until' => optional($vacancy->active_until)->format('Y-m-d'),
            'categories' => $vacancy->categories()->pluck('vacancy_categories.id')->all(),
            'location' => $metadata['location'] ?? null,
            'vacancy_type' => $metadata['vacancy_type'] ?? null,
            'employment_type' => $metadata['employment_type'] ?? null,
            'education_level' => $metadata['education_level'] ?? null,
            'experience_level' => $metadata['experience_level'] ?? null,
            'work_mode' => $metadata['work_mode'] ?? null,
            'hours' => $metadata['hours'] ?? null,
            'salary' => $metadata['salary'] ?? null,
            'volunteer_commitment' => $metadata['volunteer_commitment'] ?? null,
            'volunteer_compensation' => $metadata['volunteer_compensation'] ?? null,
            'contact_email' => $metadata['contact_email'] ?? null,
            'meta_title' => $vacancy->meta_title,
            'meta_description' => $vacancy->meta_description,
        ];

        foreach ($preserved as $field => $value) {
            if ($this->shouldPreserveField($field, $activeTab)) {
                $data[$field] = $value;
            }
        }

        return $data;
    }

    private function shouldPreserveField(string $field, string $activeTab): bool
    {
        $seoFields = ['meta_title', 'meta_description'];
        $formFields = ['form_id'];

        return match ($activeTab) {
            'seo' => ! in_array($field, $seoFields, true),
            'form' => ! in_array($field, $formFields, true),
            default => in_array($field, [...$seoFields, ...$formFields], true),
        };
    }

    private function normalizeStatus(mixed $status): string
    {
        return match ((string) $status) {
            '1', 'active', 'published', 'online' => 'published',
            '0', '2', '3', 'inactive', 'draft', 'offline', '' => 'draft',
            'archived' => 'archived',
            default => is_string($status) ? $status : 'draft',
        };
    }

    private function normalizeDate(mixed $date): ?string
    {
        if (! is_string($date) || trim($date) === '') {
            return null;
        }

        if (preg_match('/^\d{2}-\d{2}-\d{4}$/', $date) === 1) {
            return implode('-', array_reverse(explode('-', $date)));
        }

        return $date;
    }

    private function normalizedSlug(mixed $slug): ?string
    {
        if (! is_string($slug) || trim($slug) === '') {
            return null;
        }

        return Str::slug($slug);
    }
}
