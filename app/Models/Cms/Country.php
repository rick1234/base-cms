<?php

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;

class Country extends CmsModel
{
    use SoftDeletes;

    protected $table = 'countries';

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'charges_vat' => 'boolean',
            'shipping_general_cents' => 'integer',
            'shipping_envelope_cents' => 'integer',
            'shipping_small_box_cents' => 'integer',
            'shipping_big_box_cents' => 'integer',
            'timezones' => 'array',
            'is_enabled' => 'boolean',
        ]);
    }

    public function scopeEnabled(Builder $query): Builder
    {
        return $query
            ->where('is_enabled', true)
            ->where('status', 'active');
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && $this->is_enabled;
    }
}
