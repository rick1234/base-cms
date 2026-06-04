<?php

namespace Database\Seeders\Support;

use Illuminate\Support\Facades\Storage;
use RuntimeException;

class SeederFiles
{
    public static function publicImage(string $fixture, string $directory, ?string $filename = null): string
    {
        return self::copyToDisk('public', 'images/'.$fixture, $directory, $filename, true);
    }

    public static function publicDocument(string $fixture, string $directory, ?string $filename = null): string
    {
        return self::copyToDisk('public', 'documents/'.$fixture, $directory, $filename, true);
    }

    public static function privateDocument(string $fixture, string $directory, ?string $filename = null): array
    {
        $path = self::copyToDisk('local', 'documents/'.$fixture, $directory, $filename, false);

        return [
            'disk' => 'local',
            'path' => $path,
            'filename' => basename($path),
            'mime_type' => self::mimeType($fixture),
            'size' => Storage::disk('local')->size($path),
            'extension' => pathinfo($path, PATHINFO_EXTENSION),
        ];
    }

    private static function copyToDisk(
        string $disk,
        string $fixture,
        string $directory,
        ?string $filename,
        bool $publicPath,
    ): string {
        $source = self::fixturePath($fixture);
        $name = $filename ?: basename($source);
        $target = trim($directory, '/').'/'.ltrim(str_replace('\\', '/', $name), '/');
        $contents = file_get_contents($source);

        if ($contents === false) {
            throw new RuntimeException("Seeder fixture could not be read: {$fixture}");
        }

        Storage::disk($disk)->put($target, $contents);

        return $publicPath ? 'storage/'.$target : $target;
    }

    private static function fixturePath(string $fixture): string
    {
        $path = database_path('seeders/files/'.$fixture);

        if (! is_file($path)) {
            throw new RuntimeException("Seeder fixture does not exist: {$fixture}");
        }

        return $path;
    }

    private static function mimeType(string $fixture): string
    {
        return match (strtolower(pathinfo($fixture, PATHINFO_EXTENSION))) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            'pdf' => 'application/pdf',
            'txt' => 'text/plain',
            default => 'application/octet-stream',
        };
    }
}
