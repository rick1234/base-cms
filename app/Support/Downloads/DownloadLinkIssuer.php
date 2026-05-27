<?php

namespace App\Support\Downloads;

use App\Models\Cms\Download;
use App\Models\Cms\DownloadAccessToken;
use Illuminate\Support\Str;

class DownloadLinkIssuer
{
    public function issue(Download $download): string
    {
        $plainToken = hash('sha256', (string) Str::uuid().Str::random(64).microtime(true));

        DownloadAccessToken::query()->create([
            'download_id' => $download->id,
            'token_hash' => $this->hash($plainToken),
            'expires_at' => $download->link_expires_after_minutes
                ? now()->addMinutes($download->link_expires_after_minutes)
                : null,
        ]);

        return route('frontend.downloads.file', ['token' => $plainToken]);
    }

    public function hash(string $plainToken): string
    {
        return hash('sha256', $plainToken);
    }
}
