<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Cms\CmsRedirect;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RedirectController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $redirect = CmsRedirect::findForPath($request->path());

        abort_unless($redirect, 404);

        $target = $redirect->targetForRequest($request);
        $redirect->recordHit();

        return redirect()->to($target, $redirect->status_code);
    }
}
