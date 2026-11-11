<?php

namespace App\Support;

use Illuminate\Database\Connection;
use Illuminate\Support\Facades\Schema;

class PgSequences
{
    /** Postgres does not advance its id counter when ids are inserted explicitly, so set it to the highest id. */
    public static function reset(Connection $db, string $table): void
    {
        if ($db->getDriverName() !== 'pgsql' || ! Schema::connection($db->getName())->hasColumn($table, 'id')) {
            return;
        }

        $db->statement(
            "SELECT setval(pg_get_serial_sequence('{$table}', 'id'), COALESCE((SELECT MAX(id) FROM {$table}), 1), (SELECT MAX(id) FROM {$table}) IS NOT NULL)"
        );
    }
}
