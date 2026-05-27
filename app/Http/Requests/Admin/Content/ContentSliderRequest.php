<?php

namespace App\Http\Requests\Admin\Content;

use App\Models\Cms\ContentCategory;
use App\Models\Cms\ContentItem;
use Illuminate\Foundation\Http\FormRequest;

class ContentSliderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('access-admin') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'id' => $this->input('id', $this->route('id')),
            'slider_category_id' => $this->input('slider_category_id', $this->input('slider')),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'id' => ['required', 'integer'],
            'slider_category_id' => ['nullable', 'integer', 'exists:slider_categories,id'],
        ];
    }

    public function contentItem(): ?ContentItem
    {
        $id = $this->integer('id');

        return $id > 0 ? ContentItem::query()->find($id) : null;
    }

    public function contentCategory(): ?ContentCategory
    {
        $id = $this->integer('id');

        return $id > 0 ? ContentCategory::query()->find($id) : null;
    }
}
