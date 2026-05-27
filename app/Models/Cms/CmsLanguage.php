<?php

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class CmsLanguage extends CmsModel
{
    use SoftDeletes;

    protected $table = 'languages';

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'is_enabled' => 'boolean',
            'is_default' => 'boolean',
        ]);
    }

    public function scopeEnabled(Builder $query): Builder
    {
        return $query
            ->where('is_enabled', true)
            ->where('status', 'active');
    }

    public function label(): string
    {
        if ($this->native_name && $this->native_name !== $this->name) {
            return "{$this->name} ({$this->native_name})";
        }

        return (string) $this->name;
    }

    public static function directionFor(string $code): string
    {
        $primaryCode = Str::of($code)->replace('_', '-')->before('-')->lower()->toString();

        return in_array($primaryCode, ['ar', 'dv', 'fa', 'he', 'ks', 'ku', 'ps', 'sd', 'ug', 'ur', 'yi'], true)
            ? 'rtl'
            : 'ltr';
    }
}
