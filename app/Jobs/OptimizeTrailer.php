<?php

namespace App\Jobs;

use App\Models\Movie;
use App\Services\MediaOptimizer;
use App\Support\MediaStore;
use Illuminate\Foundation\Bus\Dispatchable;

/**
 * Runs after the HTTP response is sent (dispatchAfterResponse), so the uploader is not kept waiting
 * and no queue worker is needed. Until it finishes, the original upload is served.
 */
class OptimizeTrailer
{
    use Dispatchable;

    public function __construct(public int $movieId, public string $path) {}

    public function handle(MediaOptimizer $media): void
    {
        set_time_limit(0);
        ignore_user_abort(true);

        $new = $media->transcodeTrailer($this->path);
        if ($new === null) {
            // Could not compress it: still move the original upload to R2 so it is served from there.
            MediaStore::publish($this->path);

            return;
        }

        $movie = Movie::find($this->movieId);
        if ($movie && $movie->trailer_url === $this->path) {
            MediaStore::publish($new);
            $movie->update(['trailer_url' => $new]);
            MediaStore::forget($this->path);
        } else {
            // Movie was deleted or its trailer replaced while we were encoding.
            MediaStore::forget($new);
        }
    }
}
