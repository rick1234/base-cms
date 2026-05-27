<?php

namespace App\Support\Admin\Downloads;

use App\Models\Cms\Download;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DownloadFileManager
{
    public function store(UploadedFile $file): array
    {
        $extension = strtolower($file->guessExtension() ?: $file->extension() ?: 'bin');
        $path = $file->storeAs(
            'admin/downloads/'.now()->format('Y/m'),
            (string) Str::uuid().'.'.$extension,
            'local',
        );

        return [
            'file_disk' => 'local',
            'file_path' => $path,
            'url' => $path,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'file_size' => $file->getSize(),
            'type' => $extension,
        ];
    }

    public function deleteFile(Download $download): void
    {
        if ($download->file_disk && $download->file_path) {
            Storage::disk($download->file_disk)->delete($download->file_path);
        }

        $download->fill([
            'url' => '',
            'file_disk' => 'local',
            'file_path' => null,
            'original_filename' => null,
            'mime_type' => null,
            'file_size' => null,
            'type' => null,
        ])->save();
    }
}
