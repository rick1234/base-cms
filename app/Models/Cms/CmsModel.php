<?php

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

abstract class CmsModel extends Model
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
        static::creating(function (CmsModel $model): void {
            if (! $model->getAttribute('uuid')) {
                $model->setAttribute('uuid', (string) Str::uuid());
            }
        });
    }
}
