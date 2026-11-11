<?php

namespace App\Console\Commands;

use App\Models\Movie;
use App\Support\MediaStore;
use Illuminate\Console\Command;

class RenameMedia extends Command
{
    protected $signature = 'media:rename {--dry-run : show the new names without changing anything}';

    protected $description = 'Give already-uploaded posters and trailers readable names (happy-raj-2026-k3f9a2.webp) in the bucket';

    public function handle(): int
    {
        $renamed = 0;
        $skipped = 0;

        foreach (Movie::cursor() as $movie) {
            foreach (['image' => 'posters', 'trailer_url' => 'trailers'] as $column => $dir) {
                $old = $movie->{$column};
                if (! $old || ! MediaStore::hasRandomName($old)) {
                    continue;
                }

                $new = $dir.'/'.MediaStore::baseName($movie->title, $movie->year).'.'.pathinfo($old, PATHINFO_EXTENSION);

                if ($this->option('dry-run')) {
                    $this->line("{$old}  ->  {$new}");

                    continue;
                }

                // Copy first, then point the database at the new name, then delete the old file:
                // a crash in between leaves a duplicate file, never a broken link.
                if (! MediaStore::copy($old, $new)) {
                    $this->warn("#{$movie->id} {$movie->title}: {$old} not found anywhere, skipped");
                    $skipped++;

                    continue;
                }
                $movie->update([$column => $new]);
                MediaStore::forget($old);
                $this->info("#{$movie->id} {$new}");
                $renamed++;
            }
        }

        if (! $this->option('dry-run')) {
            $this->line("Done. renamed: {$renamed}, skipped: {$skipped}");
        }

        return self::SUCCESS;
    }
}
