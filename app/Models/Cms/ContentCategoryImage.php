<?php

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContentCategoryImage extends CmsModel
{
    use SoftDeletes;

    protected $table = 'content_category_images';

    public function contentCategory(): BelongsTo
    {
        return $this->belongsTo(ContentCategory::class);
    }
}
