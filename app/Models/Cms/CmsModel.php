<?php

namespace App\Models\Cms;

use App\Models\User;
use App\Support\Media\HandledImage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * @return Attribute<HandledImage, never>
     */
    protected function image(): Attribute
    {
        return Attribute::get(fn (): HandledImage => new HandledImage($this));
    }
}
