<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

/**
 * Where posters and trailers live.
 *
 * Uploads are always processed on the local "public" disk first (the optimizers need a real file).
 * When MEDIA_DISK=r2 the finished file is then pushed to Cloudflare R2 and the local copy is removed.
 * The database keeps the same relative path either way, so nothing in the movies table changes.
 */
class MediaStore
{
    private const CACHE_CONTROL = 'public, max-age=31536000, immutable'; // file names are random, so they never change

    public static function remoteName(): ?string
    {
        $name = (string) config('auracinema.media_disk', 'public');

        return $name === 'public' ? null : $name;
    }

    /** Public URL for a stored file. A file that is still on this server wins, so nothing breaks mid-migration. */
    public static function url(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        $remote = self::remoteName();
        $base = rtrim((string) config('auracinema.media_url'), '/');

        if ($remote === null || $base === '' || Storage::disk('public')->exists($path)) {
            return asset('storage/'.$path);
        }

        return $base.'/'.ltrim($path, '/');
    }

    /** Move a finished local file to the remote disk (no-op when the remote disk is not enabled). */
    public static function publish(string $path): bool
    {
        $remote = self::remoteName();
        if ($remote === null) {
            return true;
        }

        $local = Storage::disk('public');
        if (! $local->exists($path)) {
            return Storage::disk($remote)->exists($path);
        }

        $stream = $local->readStream($path);
        if (! is_resource($stream)) {
            return false;
        }

        try {
            Storage::disk($remote)->writeStream($path, $stream, ['CacheControl' => self::CACHE_CONTROL]);
        } finally {
            fclose($stream);
        }

        // Only remove the local copy once the remote copy is confirmed.
        if (! Storage::disk($remote)->exists($path)) {
            return false;
        }
        $local->delete($path);

        return true;
    }

    /** Delete a file everywhere it may be. */
    public static function forget(?string $path): void
    {
        if (! $path) {
            return;
        }

        Storage::disk('public')->delete($path);

        if ($remote = self::remoteName()) {
            Storage::disk($remote)->delete($path);
        }
    }
}
