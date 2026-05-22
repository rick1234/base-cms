<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Cms\RedirectRule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RedirectController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $redirect = RedirectRule::findForPath($request->path());

        abort_unless($redirect, 404);

        $target = $redirect->target_url;

        if ($redirect->preserve_query && $request->getQueryString()) {
            $target .= str_contains($target, '?') ? '&' : '?';
            $target .= $request->getQueryString();
        }

        return redirect()->to($target, $redirect->status_code);
    }
}
