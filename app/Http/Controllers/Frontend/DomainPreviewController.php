<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Cms\Domain;
use App\Support\Domains\DomainResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DomainPreviewController extends Controller
{
    public function switch(Request $request, Domain $domain, DomainResolver $resolver): RedirectResponse
    {
        abort_unless($resolver->isDevelopmentRequest($request), 404);

        $request->session()->put(DomainResolver::PREVIEW_SESSION_KEY, $domain->id);

        return redirect()->route('frontend.home');
    }

    public function clear(Request $request, DomainResolver $resolver): RedirectResponse
    {
        abort_unless($resolver->isDevelopmentRequest($request), 404);

        $request->session()->forget(DomainResolver::PREVIEW_SESSION_KEY);

        return redirect()->route('frontend.home');
    }
}
