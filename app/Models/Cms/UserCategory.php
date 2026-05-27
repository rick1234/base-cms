<?php

namespace App\Models\Cms;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserCategory extends CmsModel
{
    use SoftDeletes;

    protected $table = 'user_categories';

    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'is_hidden_from_navigation' => 'boolean',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order')->orderBy('name');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_category_user')
            ->withPivot('sort_order')
            ->withTimestamps()
            ->orderBy('user_category_user.sort_order')
            ->orderBy('users.name');
    }
}
