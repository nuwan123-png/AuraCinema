<?php

namespace App\Console\Commands;

use App\Support\MediaStore;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class PushMediaToR2 extends Command
{
    protected $signature = 'media:push {--dry-run : list what would be moved without moving it}';

    protected $description = 'Move existing posters/trailers from this server to Cloudflare R2 (same file names, local copy removed after upload)';

    public function handle(): int
    {
        if (MediaStore::remoteName() === null) {
            $this->error('MEDIA_DISK is not set to r2. Set it in the env file first.');

            return self::FAILURE;
        }

        $local = Storage::disk('public');
        $moved = 0;
        $failed = 0;

        foreach (['posters', 'trailers'] as $dir) {
            foreach ($local->files($dir) as $path) {
                if ($this->option('dry-run')) {
                    $this->line("would move {$path}");

                    continue;
                }

                try {
                    $ok = MediaStore::publish($path);
                } catch (\Throwable $e) {
                    $ok = false;
                    $this->warn($e->getMessage());
                }

                if ($ok) {
                    $moved++;
                    $this->info("moved {$path}");
                } else {
                    $failed++;
                    $this->error("FAILED {$path} (kept on this server)");
                }
            }
        }

        $this->line("Done. moved: {$moved}, failed: {$failed}");

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
