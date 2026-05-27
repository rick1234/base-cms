<?php

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContentBlockPartContainer extends CmsModel
{
    protected $table = 'content_block_part_containers';

    public function block(): BelongsTo
    {
        return $this->belongsTo(ContentBlock::class, 'block_id');
    }

    public function parts(): HasMany
    {
        return $this->hasMany(ContentBlockPart::class, 'container_id')->orderBy('sort_order')->orderBy('id');
    }
}
