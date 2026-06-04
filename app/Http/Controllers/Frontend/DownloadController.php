<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Frontend\DownloadPasswordRequest;
use App\Models\Cms\Download;
use App\Models\Cms\DownloadAccessToken;
use App\Support\Downloads\DownloadLinkIssuer;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadController extends Controller
{
    public function show(string $download, Request $request, DownloadLinkIssuer $issuer): View|RedirectResponse
    {
        $downloadModel = $this->downloadForKey($download);

        abort_unless($downloadModel?->hasFile(), 404);

        if ($downloadModel->is_password_protected && ! $request->session()->get($this->sessionKey($downloadModel))) {
            return view('frontend.downloads.password', [
                'download' => $downloadModel,
                'page' => (object) [
                    'title' => $downloadModel->name,
                    'meta_description' => $downloadModel->description ?: __('This download is password protected.'),
                ],
            ]);
        }

        return redirect()->away($issuer->issue($downloadModel));
    }

    public function unlock(string $download, DownloadPasswordRequest $request, DownloadLinkIssuer $issuer): RedirectResponse
    {
        $downloadModel = $this->downloadForKey($download);

        abort_unless($downloadModel?->hasFile(), 404);

        if (! $downloadModel->passwordMatches($request->validated('password'))) {
            return back()
                ->withErrors(['password' => __('The password is incorrect.')])
                ->onlyInput('password');
        }

        $request->session()->put($this->sessionKey($downloadModel), true);

        return redirect()->away($issuer->issue($downloadModel));
    }

    public function file(string $token, DownloadLinkIssuer $issuer, Request $request): StreamedResponse
    {
        $accessToken = DownloadAccessToken::query()
            ->with('download')
            ->where('token_hash', $issuer->hash($token))
            ->first();

        abort_unless($accessToken, 404);
        abort_if($accessToken->isExpired(), 410);

        $download = $accessToken->download;

        abort_unless($download?->isActive() && $download->hasFile(), 404);

        $accessToken->forceFill([
            'used_count' => $accessToken->used_count + 1,
            'first_ip_address' => $accessToken->first_ip_address ?: $request->ip(),
            'last_ip_address' => $request->ip(),
            'last_user_agent' => Str::limit((string) $request->userAgent(), 1000, ''),
            'last_used_at' => now(),
        ])->save();

        $download->recordDownload();

        $response = Storage::disk($download->file_disk)->download($download->file_path, $download->defaultFilename());
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        return $response;
    }

    private function downloadForKey(string $download): ?Download
    {
        return Download::query()
            ->active()
            ->where(function ($query) use ($download): void {
                $query->where('slug', $download);

                if (ctype_digit($download)) {
                    $query->orWhereKey((int) $download);
                }
            })
            ->first();
    }

    private function sessionKey(Download $download): string
    {
        return 'download-password-ok-'.$download->id;
    }
}
