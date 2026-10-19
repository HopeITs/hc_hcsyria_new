<?php

namespace App\Providers;

use App\Models\Project;
use App\Policies\ProjectPolicy;
use App\Queries\MemberAccessQuery;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use Illuminate\Database\Events\MigrationsStarted;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
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
     * Commands that create, drop or alter schema, or write rows. `migrate`
     * covers the whole `migrate:*` family because the check is a prefix match.
     * `db:seed` is here because a seeder writes test rows into whatever the
     * connection points at — on hcsyria_db, that is the live site.
     */
    private const SCHEMA_COMMANDS = [
        'migrate',
        'db:wipe',
        'db:seed',
        'schema:dump',
    ];

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->configureRateLimiting();
        $this->throttleLivewireUpdates();
        $this->guardLiveDatabases();
        $this->defineGates();
    }

    /* The members' section of /projects/micro-projects (the project list and map). */
    private function defineGates(): void
    {
        Gate::define('view-micro-projects', MemberAccessQuery::mayViewMicroProjects(...));

        // The staff dashboard's rules for a micro-project (roles and cities).
        Gate::policy(Project::class, ProjectPolicy::class);
    }

    /*
     * Rate limits, from the Tracking app. Keyed by IP for signed-out visitors,
     * which on the public site is everyone.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('livewire', fn (Request $request) => Limit::perMinute(180)->by(
            (string) ($request->user()?->getAuthIdentifier() ?? $request->ip()),
        ));

        // On the whole `web` group (bootstrap/app.php). An office behind one
        // IP stays far below 300 signed-out requests a minute.
        RateLimiter::for('guest', fn (Request $request) => $request->user() === null
            ? Limit::perMinute(300)->by('guest:'.$request->ip())
            : Limit::none());
    }

    /*
     * Every wire:click / wire:model on the site posts to this one endpoint
     * (the micro-projects filters, load-more, search), so it is the obvious
     * target for a resource-exhaustion attack. Persistent middleware only
     * re-applies what the page route already has, so the endpoint is
     * throttled directly. Livewire adds the `web` group (and with it CSRF)
     * itself; $path keeps its per-install endpoint path.
     */
    private function throttleLivewireUpdates(): void
    {
        Livewire::setUpdateRoute(fn ($handle, $path) => Route::post($path, $handle)
            ->middleware(['throttle:livewire']));
    }

    private function guardLiveDatabases(): void
    {
        /*
         * Laravel stops firing CommandStarting when APP_ENV is "testing"
         * (Foundation\Console\Kernel only reroutes the Symfony command events
         * outside unit tests). Without this, `APP_ENV=testing php artisan
         * migrate:fresh` — or any test using RefreshDatabase — would run
         * db:wipe against hcsyria_db before the MigrationsStarted backstop
         * below ever fired. Rerouting is idempotent, so outside tests this is
         * a no-op; it must happen before the Artisan application is built,
         * which is lazy, so `booted` is early enough.
         */
        if ($this->app->runningInConsole()) {
            $this->app->booted(function () {
                // Resolve through the contract: that is the shared instance
                // Artisan runs on. Resolving the concrete class builds a new one.
                $this->app->make(ConsoleKernel::class)->rerouteSymfonyCommandEvents();
            });
        }

        /*
         * Primary guard. CommandStarting fires before the migrator runs, and
         * critically before Laravel creates its `migrations` table — a later
         * hook lets that table be written to the target database first.
         */
        Event::listen(function (CommandStarting $event) {
            $command = (string) $event->command;

            $isSchemaCommand = false;
            foreach (self::SCHEMA_COMMANDS as $prefix) {
                if ($command === $prefix || str_starts_with($command, $prefix.':')) {
                    $isSchemaCommand = true;
                    break;
                }
            }

            if (! $isSchemaCommand) {
                return;
            }

            /*
             * The connection the command will actually use: --database when
             * given (e.g. `db:wipe --database=legacy` while the default points
             * at a scratch database), otherwise the default.
             */
            $connection = $event->input->getParameterOption('--database', null, true)
                ?: config('database.default');
            $database = config("database.connections.$connection.database");

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
            .'legacy application. Point DB_DATABASE at a development database '
            .'(for example hcsyria_new_db) and run again.'
        );
    }
}
