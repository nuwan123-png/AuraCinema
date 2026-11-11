<?php

namespace App\Console\Commands;

use App\Support\PgSequences;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class RestoreDatabase extends Command
{
    protected $signature = 'db:restore {file? : backup file name (default: the newest)} {--list : only list the backups}';

    protected $description = 'Load a backup made by db:backup into EMPTY tables (run php artisan migrate first on a fresh database)';

    public function handle(): int
    {
        $disk = Storage::disk('r2_backup');
        $files = collect($disk->files())->filter(fn ($f) => str_starts_with($f, 'db-'))->sort()->values();

        if ($this->option('list')) {
            $files->each(fn ($f) => $this->line($f));

            return self::SUCCESS;
        }

        $file = $this->argument('file') ?: $files->last();
        if (! $file || ! $disk->exists($file)) {
            $this->error('No such backup. Use --list to see them.');

            return self::FAILURE;
        }

        $data = json_decode((string) gzdecode($disk->get($file)), true);
        if (! is_array($data) || ! isset($data['tables'])) {
            $this->error('That file is not a valid backup.');

            return self::FAILURE;
        }

        foreach (BackupDatabase::TABLES as $table) {
            if (isset($data['tables'][$table]) && DB::table($table)->count() > 0) {
                $this->error("{$table} already has rows. Restore only into an empty database.");

                return self::FAILURE;
            }
        }

        DB::transaction(function () use ($data) {
            foreach (BackupDatabase::TABLES as $table) {
                $rows = $data['tables'][$table] ?? [];
                foreach (array_chunk($rows, 200) as $chunk) {
                    DB::table($table)->insert($chunk);
                }
                if ($rows !== [] && Schema::hasTable($table)) {
                    PgSequences::reset(DB::connection(), $table);
                }
                $this->info("{$table}: restored ".count($rows));
            }
        });

        return self::SUCCESS;
    }
}
