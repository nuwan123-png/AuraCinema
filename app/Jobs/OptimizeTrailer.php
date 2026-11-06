<?php

namespace App\Jobs;

use App\Models\Movie;
use App\Services\MediaOptimizer;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Storage;

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
            return;
        }

        $movie = Movie::find($this->movieId);
        if ($movie && $movie->trailer_url === $this->path) {
            $movie->update(['trailer_url' => $new]);
            Storage::disk('public')->delete($this->path);
        } else {
            // Movie was deleted or its trailer replaced while we were encoding.
            Storage::disk('public')->delete($new);
        }
    }
}
