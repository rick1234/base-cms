<?php

namespace App\Support\Admin\Faq;

use App\Models\Cms\FaqImage;
use App\Models\Cms\FaqItem;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FaqMediaManager
{
    public function storeImage(FaqItem $faqItem, UploadedFile $file, ?string $caption = null, ?Authenticatable $actor = null): FaqImage
    {
        $path = $file->storeAs(
            'admin/uploads/faq/images',
            (string) Str::uuid().'.'.($file->guessExtension() ?: $file->extension() ?: 'bin'),
            'public',
        );

        return FaqImage::query()->create([
            'faq_item_id' => $faqItem->id,
            'folder' => 'storage/admin/uploads/faq/images',
            'image_path' => 'storage/'.$path,
            'caption' => $caption ?: $file->getClientOriginalName(),
            'sort_order' => ($faqItem->images()->max('sort_order') ?? 0) + 1,
            'created_by' => $actor?->getAuthIdentifier(),
            'updated_by' => $actor?->getAuthIdentifier(),
        ]);
    }

    public function deleteMedia(Model $media, ?Authenticatable $actor = null): void
    {
        $path = $media->getAttribute('image_path') ?: $media->getAttribute('url');

        if ($path && Str::startsWith($path, 'storage/')) {
            Storage::disk('public')->delete(Str::after($path, 'storage/'));
        }

        $media->setAttribute('updated_by', $actor?->getAuthIdentifier());
        $media->save();
        $media->delete();
    }

    /**
     * @param  class-string<Model>  $modelClass
     * @param  list<int>  $ids
     */
    public function updateSortOrder(string $modelClass, array $ids, ?Authenticatable $actor = null): void
    {
        foreach ($ids as $index => $id) {
            $modelClass::query()
                ->whereKey($id)
                ->update([
                    'sort_order' => $index + 1,
                    'updated_by' => $actor?->getAuthIdentifier(),
                ]);
        }
    }
}
