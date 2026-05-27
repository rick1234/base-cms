<?php

namespace App\Actions\Admin\Downloads;

use App\Models\Cms\Download;
use App\Support\Admin\Downloads\DownloadFileManager;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UpsertDownload
{
    public function __construct(private readonly DownloadFileManager $fileManager) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data, ?Authenticatable $actor = null, ?Download $download = null, ?UploadedFile $file = null): Download
    {
        $download ??= new Download;

        $attributes = Arr::only($data, [
            'name',
            'slug',
            'description',
            'status',
            'active_from',
            'active_until',
            'sort_order',
            'is_password_protected',
            'link_expires_after_minutes',
        ]);

        if (blank($attributes['slug'] ?? null)) {
            $attributes['slug'] = Str::slug((string) $attributes['name']);
        }

        if (! empty($data['unlimited_link'])) {
            $attributes['link_expires_after_minutes'] = null;
        }

        if (! empty($attributes['is_password_protected']) && filled($data['password'] ?? null)) {
            $attributes['password_hash'] = Hash::make((string) $data['password']);
        }

        if (empty($attributes['is_password_protected'])) {
            $attributes['password_hash'] = null;
        }

        if ($file instanceof UploadedFile) {
            if ($download->exists) {
                $this->fileManager->deleteFile($download);
            }

            $attributes = [
                ...$attributes,
                ...$this->fileManager->store($file),
            ];
        } elseif (! $download->exists) {
            $attributes['url'] = '';
            $attributes['file_disk'] = 'local';
        }

        if (! $download->exists) {
            $attributes['created_by'] = $actor?->getAuthIdentifier();
        }

        $attributes['updated_by'] = $actor?->getAuthIdentifier();

        $download->fill($attributes)->save();

        $this->syncCategories($download, (array) ($data['categories'] ?? []));

        return $download->refresh();
    }

    /**
     * @param  array<int|string, mixed>  $categoryIds
     */
    private function syncCategories(Download $download, array $categoryIds): void
    {
        $ids = collect($categoryIds)
            ->map(fn (mixed $id): int => (int) $id)
            ->filter()
            ->unique()
            ->values();

        $download->categories()->sync(
            $ids->mapWithKeys(fn (int $id, int $index): array => [$id => ['sort_order' => $index + 1]])->all()
        );
    }
}
