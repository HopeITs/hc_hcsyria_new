<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Process;

/*
 * The guard in AppServiceProvider is all that stands between a mistyped
 * command and the live database, so each way in is tested. Only commands that
 * would do no harm if the guard failed are used: migrate:status only reads,
 * and there is no seeder class for db:seed to run.
 */

it('refuses schema and seed commands from the command line', function (string $command) {
    $result = Process::path(base_path())->run("php artisan {$command} --no-interaction");

    expect($result->failed())->toBeTrue()
        ->and($result->output().$result->errorOutput())->toContain('Refusing');
})->with(['migrate:status', 'db:seed']);

/*
 * Laravel does not fire CommandStarting when APP_ENV is "testing". Before the
 * guard rerouted those events itself, this let RefreshDatabase — or
 * `APP_ENV=testing php artisan migrate:fresh` — wipe hcsyria_db.
 */
it('refuses them under APP_ENV=testing', function () {
    $result = Process::path(base_path())->env(['APP_ENV' => 'testing'])
        ->run('php artisan migrate:status --no-interaction');

    expect($result->output().$result->errorOutput())->toContain('Refusing');
});

it('refuses them when called in-process, as RefreshDatabase would', function () {
    expect(fn () => Artisan::call('migrate:status'))->toThrow(RuntimeException::class, 'Refusing');
});

it('checks the connection named by --database, not only the default', function () {
    $result = Process::path(base_path())->env(['DB_DATABASE' => 'hcsyria_scratch_guard_test'])
        ->run('php artisan migrate:status --database=legacy --no-interaction');

    expect($result->output().$result->errorOutput())->toContain("against 'hcsyria_db'");
});
