<?php

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class RedirectRule extends Model
{
    protected $table = 'cms_redirect_rules';

    protected $fillable = [
        'source_path',
        'target_url',
        'status_code',
        'preserve_query',
        'enabled',
    ];

    protected function casts(): array
    {
        return [
            'status_code' => 'integer',
            'preserve_query' => 'boolean',
            'enabled' => 'boolean',
        ];
    }

    /**
     * @param  Builder<RedirectRule>  $query
     * @return Builder<RedirectRule>
     */
    public function scopeEnabled(Builder $query): Builder
    {
        return $query->where('enabled', true);
    }

    public static function findForPath(string $path): ?self
    {
        $normalizedPath = trim($path, '/');

        return self::query()
            ->enabled()
            ->where('source_path', $normalizedPath)
            ->first();
    }
}
