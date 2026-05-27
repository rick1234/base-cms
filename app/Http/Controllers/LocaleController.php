<?php

namespace App\Http\Controllers;

use App\Http\Requests\LocaleSwitchRequest;
use Illuminate\Http\RedirectResponse;

class LocaleController extends Controller
{
    public function __invoke(LocaleSwitchRequest $request): RedirectResponse
    {
        $locale = $request->locale();

        $request->session()->put('locale', $locale);

        if ($request->user()) {
            $request->user()->forceFill(['locale' => $locale])->save();
        }

        return back();
    }
}
