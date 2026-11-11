<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CopyLegacyDatabase extends Command
{
    protected $signature = 'db:copy-legacy {--dry-run : only count rows, change nothing}';

    protected $description = 'Copy catalog data (users, movies, episodes, movie_requests) from the old MySQL ("legacy" connection) into the current database';

    /** Parents first so foreign keys are satisfied. Sessions/cache/jobs are throw-away and not copied. */
    private const TABLES = ['users', 'movies', 'episodes', 'movie_requests'];

    public function handle(): int
    {
        $target = DB::connection();
        $source = DB::connection('legacy');

        $this->line('From: legacy ('.$source->getDriverName().')  To: default ('.$target->getDriverName().')');

        foreach (self::TABLES as $table) {
            if (! Schema::connection('legacy')->hasTable($table)) {
                $this->warn("{$table}: not in legacy database, skipped");

                continue;
            }
            if (! Schema::hasTable($table)) {
                $this->error("{$table}: missing in target. Run php artisan migrate first.");

                return self::FAILURE;
            }

            $count = $source->table($table)->count();
            if ($this->option('dry-run')) {
                $this->line("{$table}: {$count} rows would be copied (target currently has ".$target->table($table)->count().')');

                continue;
            }
            if ($target->table($table)->count() > 0) {
                $this->error("{$table}: target already has rows. Refusing to copy twice.");

                return self::FAILURE;
            }
        }

        if ($this->option('dry-run')) {
            return self::SUCCESS;
        }

        $target->transaction(function () use ($source, $target) {
            foreach (self::TABLES as $table) {
                if (! Schema::connection('legacy')->hasTable($table)) {
                    continue;
                }
                $n = 0;
                $source->table($table)->orderBy($this->orderColumn($table))->chunk(200, function ($rows) use ($target, $table, &$n) {
                    $target->table($table)->insert($rows->map(fn ($r) => (array) $r)->all());
                    $n += $rows->count();
                });
                $this->info("{$table}: copied {$n}");

                $this->resetSequence($target, $table);
            }
        });

        $this->info('Done.');

        return self::SUCCESS;
    }

    private function orderColumn(string $table): string
    {
        return Schema::hasColumn($table, 'id') ? 'id' : 'email';
    }

    /** Postgres does not advance its id counter when ids are inserted explicitly, so set it. */
    private function resetSequence($target, string $table): void
    {
        if ($target->getDriverName() !== 'pgsql' || ! Schema::hasColumn($table, 'id')) {
            return;
        }

        $target->statement(
            "SELECT setval(pg_get_serial_sequence('{$table}', 'id'), COALESCE((SELECT MAX(id) FROM {$table}), 1), (SELECT MAX(id) FROM {$table}) IS NOT NULL)"
        );
    }
}
