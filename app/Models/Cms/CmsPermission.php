<?php

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CmsPermission extends CmsModel
{
    protected $table = 'permissions';

    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'is_system' => 'boolean',
        ];
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(CmsRole::class, 'role_permissions', 'permission_id', 'role_id')
            ->withPivot('sort_order')
            ->withTimestamps();
    }
}
