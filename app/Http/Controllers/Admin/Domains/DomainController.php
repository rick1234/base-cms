<?php

namespace App\Http\Controllers\Admin\Domains;

use App\Actions\Admin\Domains\UpsertDomain;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Domains\DomainRequest;
use App\Models\Cms\Domain;
use App\Models\Cms\Form;
use App\Models\Cms\WebsiteTemplate;
use App\Support\Domains\DomainLanguageActivator;
use App\Support\Domains\DomainWizard;
use App\Support\Localization\TranslationRepository;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DomainController extends Controller
{
    public function index(): View
    {
        return view('admin.domains.index');
    }

    public function create(
        TranslationRepository $translations,
        DomainLanguageActivator $languageActivator,
        Request $request,
    ): View {
        $localDomain = Domain::normalizeHost(config('cms_domains.local_domain'));
        $localDomainExists = Domain::query()
            ->where('host', $localDomain)
            ->orWhereHas('aliases', fn ($query) => $query->where('host', $localDomain))
            ->exists();

        return $this->form(new Domain([
            'host' => $localDomainExists ? null : $localDomain,
            'name' => config('app.name', 'Base CMS'),
            'default_locale' => config('cms.default_locale', config('app.locale')),
            'title_separator' => '|',
            'is_active' => true,
            'is_development' => true,
        ]), $translations, $languageActivator, $request);
    }

    public function store(DomainRequest $request, UpsertDomain $upsert): RedirectResponse
    {
        $data = $request->validated();
        $step = $this->submittedStep($data);

        if ($step !== null && $step !== 'identity') {
            return redirect()->route('admin.domains.create');
        }

        $domain = $upsert->handle($data, $request->user(), step: $step);

        flash($step === null ? __('Domain created.') : __('Domain step saved.'))->success();

        return $this->redirectToStep($domain, $this->targetStep($data, $step));
    }

    public function edit(
        Domain $domain,
        TranslationRepository $translations,
        DomainLanguageActivator $languageActivator,
        Request $request,
    ): View {
        return $this->form($domain->load(['aliases', 'template']), $translations, $languageActivator, $request);
    }

    public function update(DomainRequest $request, Domain $domain, UpsertDomain $upsert): RedirectResponse
    {
        $data = $request->validated();
        $step = $this->submittedStep($data);
        $domain = $upsert->handle($data, $request->user(), $domain, $step);

        flash($step === null ? __('Domain saved.') : __('Domain step saved.'))->success();

        return $this->redirectToStep($domain, $this->targetStep($data, $step));
    }

    public function destroy(Domain $domain): RedirectResponse
    {
        $domain->delete();

        flash(__('Domain deleted.'))->success();

        return redirect()->route('admin.domains.index');
    }

    private function form(
        Domain $domain,
        TranslationRepository $translations,
        DomainLanguageActivator $languageActivator,
        Request $request,
    ): View {
        $requestedStep = old('_domain_step');

        if (! is_string($requestedStep)) {
            $requestedStep = $request->route('step') ?: $request->query('step');
        }

        $activeStep = DomainWizard::normalize(
            is_string($requestedStep) ? $requestedStep : null,
            DomainWizard::defaultStepFor($domain),
        );

        if (! $domain->exists) {
            $activeStep = 'identity';
        }

        return view('admin.domains.edit', [
            'domain' => $domain,
            'templates' => WebsiteTemplate::query()->active()->ordered()->get(),
            'languages' => $translations->enabledLanguages(),
            'languageOptions' => $languageActivator->options(),
            'forms' => Form::query()->published()->orderBy('name')->get(),
            'localDomain' => Domain::normalizeHost(config('cms_domains.local_domain')),
            'action' => $domain->exists
                ? route('admin.domains.update', $domain)
                : route('admin.domains.store'),
            'method' => $domain->exists ? 'put' : 'post',
            'deleteAction' => $domain->exists ? route('admin.domains.destroy', $domain) : null,
            'steps' => DomainWizard::steps(),
            'stepCompletion' => DomainWizard::completion($domain),
            'activeStep' => $activeStep,
            'previousStep' => DomainWizard::previous($activeStep),
            'nextStep' => DomainWizard::next($activeStep),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function submittedStep(array $data): ?string
    {
        $step = $data['_domain_step'] ?? null;

        return is_string($step) && $step !== '' ? DomainWizard::normalize($step) : null;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function targetStep(array $data, ?string $submittedStep): string
    {
        $target = $data['_next_step'] ?? null;

        if (is_string($target) && $target !== '') {
            return DomainWizard::normalize($target);
        }

        return $submittedStep === null ? 'review' : DomainWizard::next($submittedStep);
    }

    private function redirectToStep(Domain $domain, string $step): RedirectResponse
    {
        return redirect()->route('admin.domains.edit.step', [
            'domain' => $domain,
            'step' => DomainWizard::normalize($step),
        ]);
    }
}
