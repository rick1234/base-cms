<?php

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\Relations\HasMany;

class ContentBlockLayout extends CmsModel
{
    protected $table = 'content_block_layouts';

    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'columns' => 'array',
        ];
    }

    public function blocks(): HasMany
    {
        return $this->hasMany(ContentBlock::class, 'layout_id');
    }
}
