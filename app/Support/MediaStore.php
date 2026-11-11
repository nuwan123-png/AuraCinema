<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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

    /**
     * A readable, unique file name (without extension): "happy-raj-2026-k3f9a2".
     * The short random tail keeps two movies with the same title apart and makes a replaced file get a NEW
     * address (files are cached for a year, so re-using the same name would show the old file).
     */
    public static function baseName(?string $title, $year = null): string
    {
        $slug = trim(Str::slug(Str::limit((string) $title, 60, '')), '-') ?: 'movie';
        $year = (int) $year;

        // Titles are often saved as "Robin Hood (2025)": do not add a second year after it.
        $hasYear = (bool) preg_match('/-(19|20)\d\d$/', $slug);

        return $slug.(! $hasYear && $year >= 1900 && $year <= 2100 ? '-'.$year : '').'-'.Str::lower(Str::random(6));
    }

    /** True for the old random names (32-40 letters/digits) that are hard to recognise in the bucket. */
    public static function hasRandomName(?string $path): bool
    {
        return $path !== null && (bool) preg_match('/^[A-Za-z0-9]{32,40}\.[A-Za-z0-9]+$/', basename($path));
    }

    /** Copy a stored file to a new name on every disk that has it. The old file is NOT removed (call forget). */
    public static function copy(string $from, string $to): bool
    {
        $done = false;

        if (Storage::disk('public')->exists($from)) {
            Storage::disk('public')->copy($from, $to);
            $done = Storage::disk('public')->exists($to);
        }

        if (($remote = self::remoteName()) !== null && Storage::disk($remote)->exists($from)) {
            Storage::disk($remote)->copy($from, $to);
            $done = Storage::disk($remote)->exists($to) || $done;
        }

        return $done;
    }

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
