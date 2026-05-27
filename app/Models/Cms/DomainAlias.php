<?php

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DomainAlias extends CmsModel
{
    protected $table = 'domain_aliases';

    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'is_primary' => 'boolean',
        ];
    }

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }
}
