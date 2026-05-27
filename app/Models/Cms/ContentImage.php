<?php

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContentImage extends CmsModel
{
    use SoftDeletes;

    protected $table = 'content_images';

    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'is_decorative' => 'boolean',
        ];
    }

    public function contentItem(): BelongsTo
    {
        return $this->belongsTo(ContentItem::class);
    }
}
