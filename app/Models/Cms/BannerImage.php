<?php

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class BannerImage extends CmsModel
{
    use SoftDeletes;

    protected $table = 'banner_images';

    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'is_decorative' => 'boolean',
            'file_size' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function banner(): BelongsTo
    {
        return $this->belongsTo(Banner::class, 'banner_id');
    }
}
