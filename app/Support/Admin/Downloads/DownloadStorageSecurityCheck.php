<?php

namespace App\Support\Admin\Downloads;

use App\Models\Cms\Download;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class DownloadStorageSecurityCheck
{
    /**
     * @return array{passed: bool, checks: list<array{label: string, passed: bool, detail: string}>}
     */
    public function run(Download $download): array
    {
        $path = (string) $download->file_path;
        $disk = (string) $download->file_disk;
        $checks = [
            $this->check(__('File exists in Laravel storage'), $download->hasFile(), $download->hasFile() ? $path : __('No stored file found.')),
            $this->check(__('File uses private storage disk'), $disk === 'local', $disk ?: __('No disk configured.')),
            $this->check(__('File is not present in public path'), ! file_exists(public_path($path)), public_path($path)),
            $this->check(__('File is not present on public storage disk'), $path === '' || ! Storage::disk('public')->exists($path), 'public/'.$path),
            $this->check(__('Password protection can be used'), Schema::hasColumn('downloads', 'password_hash'), __('Password field is available.')),
            $this->check(
                __('Enabled password protection has a password hash'),
                ! $download->is_password_protected || filled($download->password_hash),
                $download->is_password_protected ? __('Password hash found.') : __('Password protection is disabled for this file.'),
            ),
        ];

        return [
            'passed' => collect($checks)->every(fn (array $check): bool => $check['passed']),
            'checks' => $checks,
        ];
    }

    /**
     * @return array{label: string, passed: bool, detail: string}
     */
    private function check(string $label, bool $passed, string $detail): array
    {
        return compact('label', 'passed', 'detail');
    }
}
