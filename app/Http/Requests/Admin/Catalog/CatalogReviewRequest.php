<?php

namespace App\Http\Requests\Admin\Catalog;

use App\Models\Cms\CatalogReview;
use Illuminate\Foundation\Http\FormRequest;

class CatalogReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('access-admin') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'id' => ['nullable', 'integer', 'exists:catalog_reviews,id'],
            'catalog_product_id' => ['required', 'integer', 'exists:catalog_products,id'],
            'author_name' => ['nullable', 'string', 'max:255'],
            'author_email' => ['nullable', 'email:rfc', 'max:255'],
            'rating' => ['nullable', 'integer', 'min:1', 'max:5'],
            'status' => ['required', 'string', 'in:pending,published,rejected'],
            'title' => ['nullable', 'string'],
            'content' => ['nullable', 'string'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'catalog_product_id' => $this->input('catalog_product_id', $this->input('artikel_id')),
            'author_name' => $this->input('author_name', $this->input('user')),
            'status' => $this->normalizedStatus($this->input('status', 'published')),
        ]);
    }

    public function review(): ?CatalogReview
    {
        $id = $this->integer('id');

        return $id > 0 ? CatalogReview::query()->findOrFail($id) : null;
    }

    private function normalizedStatus(mixed $status): string
    {
        return match ((string) $status) {
            '1', 'active', 'published' => 'published',
            '0', '2', 'inactive', 'rejected' => 'rejected',
            'pending' => 'pending',
            default => 'published',
        };
    }
}
