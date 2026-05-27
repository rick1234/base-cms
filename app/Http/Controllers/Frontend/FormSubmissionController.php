<?php

namespace App\Http\Controllers\Frontend;

use App\Actions\Forms\SubmitForm;
use App\Http\Controllers\Controller;
use App\Http\Requests\Forms\FormSubmissionRequest;
use App\Models\Cms\Form;
use Illuminate\Http\RedirectResponse;

class FormSubmissionController extends Controller
{
    public function store(FormSubmissionRequest $request, Form $form, SubmitForm $submitForm): RedirectResponse
    {
        abort_unless($form->isActive(), 404);

        $submitForm->handle($form, $request);

        flash($form->success_message ?: __('Thank you. Your message was sent.'))->success();

        if (filled($form->settings['redirect_url'] ?? null)) {
            return redirect()->to($form->settings['redirect_url']);
        }

        return back();
    }
}
