<?php

namespace App\Console\Commands;

use App\Models\Movie;
use App\Services\MediaOptimizer;
use App\Support\MediaStore;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class OptimizeMedia extends Command
{
    protected $signature = 'media:optimize {--posters : only posters} {--trailers : only trailers}';

    protected $description = 'Re-encode already-uploaded posters (480px WebP) and trailers (720p faststart MP4)';

    public function handle(MediaOptimizer $media): int
    {
        ini_set('memory_limit', '512M');
        $all = ! $this->option('posters') && ! $this->option('trailers');
        $disk = Storage::disk('public');

        foreach (Movie::cursor() as $movie) {
            if (($all || $this->option('posters')) && $movie->image && ! str_ends_with($movie->image, '.webp') && $disk->exists($movie->image)) {
                $this->line("poster  #{$movie->id} {$movie->title}");
                if ($new = $media->reencodePoster($movie->image, MediaStore::baseName($movie->title, $movie->year))) {
                    $old = $movie->image;
                    $movie->update(['image' => $new]);
                    $disk->delete($old);
                }
            }

            if (($all || $this->option('trailers')) && $movie->trailer_url && $disk->exists($movie->trailer_url)) {
                $this->line("trailer #{$movie->id} {$movie->title} (this can take a while)");
                if ($new = $media->transcodeTrailer($movie->trailer_url, MediaStore::baseName($movie->title, $movie->year))) {
                    $old = $movie->trailer_url;
                    $movie->update(['trailer_url' => $new]);
                    $disk->delete($old);
                }
            }
        }

        $this->info('Done.');

        return self::SUCCESS;
    }
}
