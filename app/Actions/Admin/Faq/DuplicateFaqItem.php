<?php

namespace App\Actions\Admin\Faq;

use App\Models\Cms\FaqItem;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Str;

class DuplicateFaqItem
{
    public function handle(FaqItem $faqItem, ?Authenticatable $actor = null): FaqItem
    {
        $copy = $faqItem->replicate(['uuid', 'created_by', 'updated_by']);
        $copy->question = __('Copy of :question', ['question' => $faqItem->question]);
        $copy->slug = ($faqItem->slug ?: Str::slug($faqItem->question)).'-copy-'.Str::lower(Str::random(5));
        $copy->status = 'draft';
        $copy->created_by = $actor?->getAuthIdentifier();
        $copy->updated_by = $actor?->getAuthIdentifier();
        $copy->save();

        $copy->categories()->sync(
            $faqItem->categories
                ->mapWithKeys(fn ($category): array => [$category->id => ['sort_order' => $category->pivot->sort_order]])
                ->all()
        );

        return $copy->refresh();
    }
}
