<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Applies database/cutover/* to whatever database .env points at: local copy,
 * staging or the online one. The steps are guarded (hasTable checks), so a
 * re-run is harmless. Never use migrate:fresh on a legacy-shaped database.
 */
class RunCutover extends Command
{
    protected $signature = 'cutover:run
        {--backup-done : Confirm a backup of this database was taken (required)}
        {--force : Run without the interactive prompt (needed in production)}';

    protected $description = 'Drop unused legacy tables, rename the rest and tidy indexes (database/cutover)';

    public function handle(): int
    {
        $db = config('database.connections.'.config('database.default').'.database');

        if (! $this->option('backup-done')) {
            $this->error("Refusing to run: take a backup of '{$db}' first, then pass --backup-done.");

            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->confirm("This DROPS tables and renames others in '{$db}'. Continue?")) {
            return self::FAILURE;
        }

        return $this->call('migrate', [
            '--path' => 'database/cutover',
            '--force' => true,
        ]);
    }
}
