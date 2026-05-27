<?php

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DownloadCategory extends CmsModel
{
    use SoftDeletes;

    protected $table = 'download_categories';

    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'metadata' => 'array',
            'is_hidden_from_navigation' => 'boolean',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order')->orderBy('name');
    }

    public function downloads(): BelongsToMany
    {
        return $this->belongsToMany(Download::class, 'download_category_download')
            ->withPivot('sort_order')
            ->withTimestamps()
            ->orderBy('download_category_download.sort_order')
            ->orderBy('downloads.name');
    }
}
