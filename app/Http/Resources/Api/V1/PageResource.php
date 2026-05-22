<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PageResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,
            'slug' => $this->slug,
            'title' => $this->title,
            'navigation_label' => $this->navigation_label,
            'excerpt' => $this->excerpt,
            'body' => $this->body,
            'template' => $this->template,
            'status' => $this->status,
            'published_at' => $this->published_at?->toIso8601String(),
            'seo' => [
                'title' => $this->meta_title,
                'description' => $this->meta_description,
                'canonical_url' => $this->canonical_url,
                'open_graph' => [
                    'title' => $this->og_title,
                    'description' => $this->og_description,
                    'image' => $this->og_image,
                ],
            ],
        ];
    }
}
