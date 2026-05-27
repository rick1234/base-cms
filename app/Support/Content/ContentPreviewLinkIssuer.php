<?php

namespace App\Support\Content;

use App\Models\Cms\ContentItem;
use App\Models\Cms\ContentPreviewToken;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ContentPreviewLinkIssuer
{
    private const EXPIRY_MINUTES = 30;

    public function issue(ContentItem $contentItem, Request $request, ?User $user = null): string
    {
        $plainToken = hash('sha256', (string) Str::uuid().Str::random(64).microtime(true));

        ContentPreviewToken::query()
            ->where('content_item_id', $contentItem->id)
            ->where('expires_at', '<', now())
            ->delete();

        ContentPreviewToken::query()->create([
            'content_item_id' => $contentItem->id,
            'user_id' => $user?->id,
            'token_hash' => $this->hash($plainToken),
            'ip_address' => (string) $request->ip(),
            'expires_at' => now()->addMinutes(self::EXPIRY_MINUTES),
        ]);

        return route('frontend.content.preview', ['token' => $plainToken]);
    }

    public function hash(string $plainToken): string
    {
        return hash('sha256', $plainToken);
    }
}
