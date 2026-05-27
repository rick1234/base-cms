<?php

namespace App\Models\Cms;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;

class CmsRole extends CmsModel
{
    protected $table = 'roles';

    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'is_system' => 'boolean',
        ];
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(CmsPermission::class, 'role_permissions', 'role_id', 'permission_id')
            ->withPivot('sort_order')
            ->withTimestamps()
            ->orderBy('permissions.sort_order')
            ->orderBy('permissions.name');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'role_user', 'role_id', 'user_id')
            ->withPivot('is_primary')
            ->withTimestamps()
            ->orderBy('users.name');
    }

    /**
     * @param  iterable<int, int|string>|Collection<int, int|string>  $permissionIds
     */
    public function syncPermissionIds(iterable $permissionIds): void
    {
        $ids = collect($permissionIds)
            ->map(fn (int|string $id): int => (int) $id)
            ->filter()
            ->unique()
            ->values();

        $this->permissions()->sync(
            $ids->mapWithKeys(fn (int $id, int $index): array => [$id => ['sort_order' => $index + 1]])->all()
        );
    }

    public function hasPermission(string $permissionKey): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        if (! $this->relationLoaded('permissions')) {
            $this->load('permissions');
        }

        return $this->permissions->contains('permission_key', $permissionKey);
    }
}
