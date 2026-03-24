<?php

namespace Database\Seeders\Concerns;

use Illuminate\Support\Facades\DB;

trait IdentityInsert
{
    /**
     * Insert rows with explicit id values on SQL Server.
     *
     * Wraps the insert in IDENTITY_INSERT ON/OFF when running on sqlsrv.
     * Falls back to plain insert for SQLite/MySQL/PostgreSQL.
     */
    protected function insertWithIdentity(string $table, array $rows): void
    {
        if (empty($rows)) {
            return;
        }

        $driver = DB::getDriverName();

        if ($driver === 'sqlsrv') {
            DB::unprepared("SET IDENTITY_INSERT [{$table}] ON;");
        }

        // SQL Server supports max 2100 parameters per query
        $columnsPerRow = count(reset($rows));
        $chunkSize = $driver === 'sqlsrv'
            ? max(1, intdiv(2000, max(1, $columnsPerRow)))
            : 100;

        foreach (array_chunk($rows, $chunkSize) as $chunk) {
            DB::table($table)->insert($chunk);
        }

        if ($driver === 'sqlsrv') {
            DB::unprepared("SET IDENTITY_INSERT [{$table}] OFF;");
        }
    }
}
