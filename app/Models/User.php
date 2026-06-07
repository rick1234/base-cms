<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Cms\CmsPermission;
use App\Models\Cms\CmsRole;
use App\Models\Cms\UserCategory;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable([
    'name',
    'email',
    'password',
    'two_factor_secret',
    'two_factor_confirmed_at',
    'two_factor_disabled_at',
    'is_admin',
    'username',
    'locale',
    'active_from',
    'active_until',
    'is_active',
    'salutation',
    'first_name',
    'middle_name',
    'last_name',
    'gender',
    'street',
    'house_number',
    'house_number_addition',
    'postal_code',
    'city',
    'country_code',
    'phone',
    'company_name',
    'image_path',
    'role_id',
    'created_by',
    'updated_by',
    'legacy_id',
    'legacy_payload',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'is_admin' => 'boolean',
            'two_factor_secret' => 'encrypted',
            'two_factor_confirmed_at' => 'datetime',
            'two_factor_disabled_at' => 'datetime',
            'active_from' => 'date',
            'active_until' => 'date',
            'last_login_at' => 'datetime',
            'is_active' => 'boolean',
            'legacy_payload' => 'array',
            'password' => 'hashed',
        ];
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(UserCategory::class, 'user_category_user')
            ->withPivot('sort_order')
            ->withTimestamps()
            ->orderBy('user_categories.sort_order')
            ->orderBy('user_categories.name');
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(CmsRole::class, 'role_id');
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(CmsRole::class, 'role_user', 'user_id', 'role_id')
            ->withPivot('is_primary')
            ->withTimestamps()
            ->orderBy('roles.name');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(self::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(self::class, 'updated_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->where(function (Builder $query): void {
                $query->whereNull('active_from')->orWhereDate('active_from', '<=', now());
            })
            ->where(function (Builder $query): void {
                $query->whereNull('active_until')->orWhereDate('active_until', '>=', now());
            });
    }

    public function isActive(): bool
    {
        if ($this->is_active === false) {
            return false;
        }

        if ($this->active_from && $this->active_from->isFuture()) {
            return false;
        }

        if ($this->active_until && $this->active_until->isPast()) {
            return false;
        }

        return true;
    }

    public function isRevoked(): bool
    {
        return $this->active_until !== null && $this->active_until->isPast();
    }

    public function hasTwoFactorEnabled(): bool
    {
        return filled($this->two_factor_secret) && $this->two_factor_confirmed_at !== null && $this->two_factor_disabled_at === null;
    }

    public function displayName(): string
    {
        return $this->username ?: $this->name ?: $this->email;
    }

    public function fullName(): string
    {
        $fullName = trim(collect([$this->first_name, $this->middle_name, $this->last_name])
            ->filter()
            ->implode(' '));

        return $fullName ?: ($this->name ?: $this->username ?: $this->email);
    }

    public function hasAdminPermission(string $permissionKey): bool
    {
        if (! $this->isActive()) {
            return false;
        }

        if ($this->is_admin) {
            return true;
        }

        $roles = $this->effectiveRoles();

        if ($roles->isEmpty()) {
            return false;
        }

        return CmsPermission::query()
            ->where('permission_key', $permissionKey)
            ->whereHas('roles', fn ($query) => $query->whereIn('roles.id', $roles->pluck('id')))
            ->exists();
    }

    public function isSuperUser(): bool
    {
        return $this->isActive() && $this->is_admin;
    }

    /**
     * @return Collection<int, CmsRole>
     */
    public function effectiveRoles(): \Illuminate\Support\Collection
    {
        $roles = $this->relationLoaded('roles') ? $this->roles : $this->roles()->get();

        if ($this->role_id && ! $roles->contains('id', $this->role_id)) {
            $primaryRole = $this->relationLoaded('role') ? $this->role : $this->role()->first();

            if ($primaryRole) {
                $roles->push($primaryRole);
            }
        }

        return $roles
            ->filter(fn (CmsRole $role): bool => $role->status === 'active')
            ->unique('id')
            ->values();
    }
}
