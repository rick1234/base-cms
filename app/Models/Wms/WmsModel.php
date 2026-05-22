<?php

namespace App\Models\Wms;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

abstract class WmsModel extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'legacy_payload' => 'array',
            'active_from' => 'date',
            'active_until' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (WmsModel $model): void {
            if (! $model->getAttribute('uuid')) {
                $model->setAttribute('uuid', (string) Str::uuid());
            }
        });
    }
}
