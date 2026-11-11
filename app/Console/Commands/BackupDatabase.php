<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class BackupDatabase extends Command
{
    /** Parents first, so a restore satisfies the foreign keys. */
    public const TABLES = ['users', 'movies', 'episodes', 'movie_requests'];

    protected $signature = 'db:backup {--keep=14 : how many backups to keep}';

    protected $description = 'Save users, movies, episodes and requests as a compressed file in the private backup bucket';

    public function handle(): int
    {
        if (! config('filesystems.disks.r2_backup.bucket')) {
            $this->error('R2_BACKUP_BUCKET is not set.');

            return self::FAILURE;
        }

        $data = ['made_at' => now()->toIso8601String(), 'tables' => []];
        foreach (self::TABLES as $table) {
            if (Schema::hasTable($table)) {
                $data['tables'][$table] = DB::table($table)->get()->map(fn ($r) => (array) $r)->all();
            }
        }

        $name = 'db-'.now()->format('Y-m-d_His').'.json.gz';
        $disk = Storage::disk('r2_backup');
        $disk->put($name, gzencode(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), 9));

        if (! $disk->exists($name) || $disk->size($name) < 20) {
            $this->error('Backup was not saved correctly.');

            return self::FAILURE;
        }

        $counts = collect($data['tables'])->map(fn ($rows, $t) => $t.'='.count($rows))->implode(' ');
        $this->info("Saved {$name} ({$counts})");

        // Keep only the newest N (names sort by date).
        $old = collect($disk->files())->filter(fn ($f) => str_starts_with($f, 'db-'))->sort()->values();
        $old->slice(0, max(0, $old->count() - max(1, (int) $this->option('keep'))))->each(function ($f) use ($disk) {
            $disk->delete($f);
            $this->line("Removed old backup {$f}");
        });

        return self::SUCCESS;
    }
}
