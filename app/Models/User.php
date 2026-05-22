<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable([
    'name',
    'email',
    'password',
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
            'active_from' => 'date',
            'active_until' => 'date',
            'is_active' => 'boolean',
            'legacy_payload' => 'array',
            'password' => 'hashed',
        ];
    }
}
