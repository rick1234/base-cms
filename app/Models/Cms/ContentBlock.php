<?php

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContentBlock extends CmsModel
{
    protected $table = 'content_blocks';

    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'configuration' => 'array',
        ];
    }

    public function contentItem(): BelongsTo
    {
        return $this->belongsTo(ContentItem::class);
    }

    public function layout(): BelongsTo
    {
        return $this->belongsTo(ContentBlockLayout::class, 'layout_id');
    }

    public function containers(): HasMany
    {
        return $this->hasMany(ContentBlockPartContainer::class, 'block_id')->orderBy('sort_order')->orderBy('id');
    }
}
