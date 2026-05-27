<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Cms\ContentItem;
use App\Models\Cms\ContentPreviewToken;
use App\Support\Content\ContentPreviewLinkIssuer;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ContentPreviewController extends Controller
{
    public function show(string $token, Request $request, ContentPreviewLinkIssuer $issuer): Response
    {
        $previewToken = ContentPreviewToken::query()
            ->with('contentItem')
            ->where('token_hash', $issuer->hash($token))
            ->first();

        abort_unless($previewToken, 404);
        abort_if($previewToken->isExpired(), 404);
        abort_unless($previewToken->allowsIp($request->ip()), 404);

        $contentItem = $previewToken->contentItem;

        abort_unless($contentItem instanceof ContentItem, 404);

        $previewToken->fill([
            'used_count' => $previewToken->used_count + 1,
            'last_used_at' => now(),
        ])->save();

        return response()
            ->view('frontend.content.show', [
                'contentItem' => $contentItem,
                'isPreview' => true,
                'page' => $contentItem,
                'robots' => 'noindex, nofollow, noarchive',
            ])
            ->header('X-Robots-Tag', 'noindex, nofollow, noarchive')
            ->header('Cache-Control', 'private, no-store, max-age=0');
    }
}
