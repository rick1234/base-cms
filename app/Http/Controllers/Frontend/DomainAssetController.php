<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Support\Domains\DomainResolver;
use App\Support\Domains\DomainThemeCss;
use Illuminate\Http\Response;

class DomainAssetController extends Controller
{
    public function theme(DomainThemeCss $themeCss): Response
    {
        $domain = app()->bound(DomainResolver::CONTAINER_KEY)
            ? app(DomainResolver::CONTAINER_KEY)
            : null;

        return response($themeCss->render($domain), 200, [
            'Content-Type' => 'text/css; charset=UTF-8',
            'Cache-Control' => app()->environment('local') ? 'no-store' : 'public, max-age=300',
        ]);
    }
}
