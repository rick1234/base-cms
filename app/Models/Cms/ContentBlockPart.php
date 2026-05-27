<?php

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentBlockPart extends CmsModel
{
    protected $table = 'content_block_parts';

    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'settings' => 'array',
        ];
    }

    public function container(): BelongsTo
    {
        return $this->belongsTo(ContentBlockPartContainer::class, 'container_id');
    }
}
