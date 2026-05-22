<?php

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Module extends Model
{
    protected $table = 'cms_modules';

    protected $fillable = [
        'uuid',
        'handle',
        'name',
        'description',
        'base_owned',
        'enabled',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'base_owned' => 'boolean',
            'enabled' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Module $module): void {
            if (! $module->getAttribute('uuid')) {
                $module->setAttribute('uuid', (string) Str::uuid());
            }
        });
    }
}
