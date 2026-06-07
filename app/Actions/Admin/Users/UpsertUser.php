<?php

namespace App\Actions\Admin\Users;

use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UpsertUser
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data, ?Authenticatable $actor = null, ?User $user = null, ?UploadedFile $image = null): User
    {
        $user ??= new User;
        $categoryIds = collect($data['categories'] ?? [])
            ->map(fn (mixed $id): int => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();
        $roleIds = collect($data['roles'] ?? [])
            ->map(fn (mixed $id): int => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();

        $attributes = Arr::only($data, [
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
            'role_id',
        ]);

        if (blank($attributes['password'] ?? null) && ! $user->exists) {
            $attributes['password'] = Str::password(32);
        }

        if (blank($attributes['password'] ?? null)) {
            unset($attributes['password']);
        }

        if (blank($attributes['username'] ?? null)) {
            $attributes['username'] = null;
        }

        if (blank($attributes['role_id'] ?? null)) {
            $attributes['role_id'] = $roleIds[0] ?? null;
        }

        if ($attributes['role_id'] && ! in_array((int) $attributes['role_id'], $roleIds, true)) {
            array_unshift($roleIds, (int) $attributes['role_id']);
        }

        if ($image instanceof UploadedFile) {
            $attributes['image_path'] = $this->storeImage($image);
        }

        if (! $user->exists) {
            $attributes['created_by'] = $actor?->getAuthIdentifier();
        }

        $attributes['updated_by'] = $actor?->getAuthIdentifier();

        $user->fill($attributes)->save();
        $user->categories()->sync($categoryIds);
        $user->roles()->sync(
            collect($roleIds)
                ->mapWithKeys(fn (int $roleId, int $index): array => [$roleId => ['is_primary' => $index === 0]])
                ->all()
        );

        return $user->refresh();
    }

    public function deleteImage(User $user): void
    {
        if ($user->image_path && Str::startsWith($user->image_path, 'storage/')) {
            Storage::disk('public')->delete(Str::after($user->image_path, 'storage/'));
        }

        $user->forceFill(['image_path' => null])->save();
    }

    private function storeImage(UploadedFile $image): string
    {
        $extension = $image->guessExtension() ?: $image->extension() ?: 'bin';
        $path = $image->storeAs('admin/uploads/users', (string) Str::uuid().'.'.$extension, 'public');

        return 'storage/'.$path;
    }
}
