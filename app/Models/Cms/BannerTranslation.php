<?php

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BannerTranslation extends CmsModel
{
    protected $table = 'banner_translations';

    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'metadata' => 'array',
        ];
    }

    public function banner(): BelongsTo
    {
        return $this->belongsTo(Banner::class, 'banner_id');
    }
}
