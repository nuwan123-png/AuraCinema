<?php

namespace App\Services;

use App\Support\MediaStore;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Symfony\Component\Process\Process;

class MediaOptimizer
{
    private const POSTER_WIDTH = 480;       // grid cards + hero are never wider than this on screen
    private const GALLERY_WIDTH = 1280;     // gallery pictures open large in the viewer
    private const CAST_WIDTH = 240;         // cast photos are small cards
    private const MAX_PIXELS = 24_000_000;  // decompression-bomb guard (24MP ~ 96MB in GD)

    /** Resize an uploaded poster to a small WebP and return its path on the public disk. */
    public function storePoster(UploadedFile $file, ?string $base = null): string
    {
        try {
            return $this->toWebp($file->getRealPath(), $base);
        } catch (RuntimeException $e) {
            throw ValidationException::withMessages(['image' => $e->getMessage()]);
        }
    }

    /** Resize a gallery picture (max 1280px wide WebP) and return its path. */
    public function storeGalleryImage(UploadedFile $file, string $base): string
    {
        return $this->storeOrFail($file, 'gallery', 'gallery_new', $base, self::GALLERY_WIDTH);
    }

    /** Resize a cast photo (max 240px wide WebP) and return its path. */
    public function storeCastPhoto(UploadedFile $file, string $base): string
    {
        return $this->storeOrFail($file, 'cast', 'cast', $base, self::CAST_WIDTH);
    }

    private function storeOrFail(UploadedFile $file, string $folder, string $errorKey, string $base, int $width): string
    {
        try {
            return $this->toWebp($file->getRealPath(), $base, $folder, $width);
        } catch (RuntimeException $e) {
            throw ValidationException::withMessages([$errorKey => $e->getMessage()]);
        }
    }

    /** Re-encode a poster that is already stored. Returns the new path, or null if it cannot be read. */
    public function reencodePoster(string $path, ?string $base = null): ?string
    {
        try {
            return $this->toWebp(Storage::disk('public')->path($path), $base);
        } catch (RuntimeException $e) {
            Log::warning('Poster re-encode failed', ['path' => $path, 'error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Transcode to 720p-ish H.264/AAC with the moov atom at the front (+faststart),
     * so playback starts immediately and seeking works over HTTP range requests.
     */
    public function transcodeTrailer(string $path, ?string $base = null): ?string
    {
        $disk = Storage::disk('public');
        $in = $disk->path($path);
        $outRel = 'trailers/'.($base ?: Str::random(32)).'.mp4';
        $out = $disk->path($outRel);

        $process = new Process([
            'nice', '-n', '10', 'ffmpeg', '-nostdin', '-y', '-i', $in,
            '-vf', "scale='min(1280,iw)':-2",
            '-c:v', 'libx264', '-preset', 'veryfast', '-crf', '28',
            '-maxrate', '2500k', '-bufsize', '5000k', '-pix_fmt', 'yuv420p',
            '-c:a', 'aac', '-b:a', '96k',
            '-movflags', '+faststart',
            $out,
        ]);
        $process->setTimeout(3600);
        $process->run();

        if (! $process->isSuccessful() || ! $disk->exists($outRel)) {
            Log::warning('Trailer transcode failed', ['path' => $path, 'stderr' => substr($process->getErrorOutput(), -1500)]);
            $disk->delete($outRel);

            return null;
        }

        return $outRel;
    }

    private function toWebp(string $absolutePath, ?string $base = null, string $folder = 'posters', int $width = self::POSTER_WIDTH): string
    {
        $info = @getimagesize($absolutePath);
        if (! $info) {
            throw new RuntimeException('The poster is not a valid image.');
        }
        [$w, $h] = $info;
        if ($w * $h > self::MAX_PIXELS) {
            throw new RuntimeException('The poster resolution is too large (max ~24 megapixels).');
        }

        $src = @imagecreatefromstring((string) file_get_contents($absolutePath));
        if (! $src) {
            throw new RuntimeException('The poster could not be decoded.');
        }

        $tw = min($width, $w);
        $th = max(1, (int) round($h * $tw / $w));
        $dst = imagecreatetruecolor($tw, $th);
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $tw, $th, $w, $h);

        ob_start();
        imagewebp($dst, null, 80);
        $binary = (string) ob_get_clean();
        unset($src, $dst);

        $rel = $folder.'/'.($base ?: Str::random(32)).'.webp';
        Storage::disk('public')->put($rel, $binary);
        MediaStore::publish($rel);

        return $rel;
    }
}
