<?php

namespace App\Providers;

use Illuminate\Console\Events\CommandStarting;
use Illuminate\Database\Events\MigrationsStarted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use RuntimeException;

class AppServiceProvider extends ServiceProvider
{
    /*
     * Databases that belong to the live legacy application. Schema commands
     * must never run against these: the schema in database/migrations mirrors
     * the legacy database, so applying it there would attempt to recreate
     * existing tables, and any destructive migration would hit production data.
     *
     * The legacy connection is read-only by intent; this makes that intent
     * enforceable rather than a convention someone can forget.
     */
    private const PROTECTED_DATABASES = [
        'hcsyria_db',
        'hcsyria_disc_a',
        'hcsyria_disc_d',
        'hcsyria_disc_h',
        'hcsyria_f2f_ajudy',
        'hcsyria_f2f_aleppo',
        'hcsyria_financial',
        'hcsyria_micro',
        'hcsyria_micro_d',
        'hcsyria_micro_h',
        'hcsyria_micro_l',
        'hcsyria_micro_w',
    ];

    /*
     * Commands that create, drop or alter schema. `migrate` covers the whole
     * `migrate:*` family because the check is a prefix match.
     */
    private const SCHEMA_COMMANDS = [
        'migrate',
        'db:wipe',
        'schema:dump',
    ];

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        /*
         * Primary guard. CommandStarting fires before the migrator runs, and
         * critically before Laravel creates its `migrations` table — a later
         * hook lets that table be written to the target database first.
         */
        Event::listen(function (CommandStarting $event) {
            $command = (string) $event->command;

            $isSchemaCommand = false;
            foreach (self::SCHEMA_COMMANDS as $prefix) {
                if ($command === $prefix || str_starts_with($command, $prefix . ':')) {
                    $isSchemaCommand = true;
                    break;
                }
            }

            if (! $isSchemaCommand) {
                return;
            }

            $default  = config('database.default');
            $database = config("database.connections.$default.database");

            $this->refuseIfProtected($database, $command);
        });

        /*
         * Defence in depth: catches migrations triggered in-process (tests,
         * Artisan::call) where no console command event is dispatched.
         *
         * MigrationsStarted carries only $method and $options — the connection
         * name arrives as options['database'] when --database was passed, and
         * is otherwise the default connection.
         */
        Event::listen(function (MigrationsStarted $event) {
            $connection = $event->options['database'] ?? config('database.default');

            $this->refuseIfProtected(
                DB::connection($connection)->getDatabaseName(),
                'migrate'
            );
        });
    }

    private function refuseIfProtected(?string $database, string $command): void
    {
        if ($database === null || ! in_array($database, self::PROTECTED_DATABASES, true)) {
            return;
        }

        throw new RuntimeException(
            "Refusing to run '{$command}' against '{$database}': it belongs to the live "
            . 'legacy application. Point DB_DATABASE at a development database '
            . '(for example hcsyria_new_db) and run again.'
        );
    }
}
