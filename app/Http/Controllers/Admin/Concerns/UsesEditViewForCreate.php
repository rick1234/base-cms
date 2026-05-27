<?php

namespace App\Http\Controllers\Admin\Concerns;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

trait UsesEditViewForCreate
{
    public function create(Request $request): View
    {
        return $this->edit($request);
    }
}
