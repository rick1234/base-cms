<?php

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\SoftDeletes;

class IsoLanguage extends CmsModel
{
    use SoftDeletes;

    protected $table = 'iso_languages';
}
