<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

/*
 * Every error page uses the legacy 404 design (resources/views/errors). The
 * legacy 500 view crashed on an undefined variable and visitors got Symfony's
 * bare page, so these also prove each page renders with nothing behind it.
 */

beforeEach(function () {
    config(['app.debug' => false]);

    Route::get('/_test/abort/{status}', fn (int $status) => abort($status));
    Route::get('/_test/crash', fn () => throw new RuntimeException('boom'));
    Route::get('/_test/query', fn () => DB::connection('mariadb')->select('select 1'));
});

it('renders the legacy-designed page for each status', function (int $status, string $heading) {
    $title = $status === 503 ? 'Site Maintenance' : "Error {$status}";

    $response = $this->get("/_test/abort/{$status}");

    $response->assertStatus($status)
        ->assertSee("<title>{$title}</title>", false)
        ->assertSee($heading, false)
        ->assertSee('Back to Homepage')
        ->assertSee('id="footer"', false)
        ->assertSee('class="site-nav"', false);
})->with([
    [401, 'Please sign in'],
    [403, 'This page isn&#039;t available to you'],
    [404, 'This page isn&#039;t available'],
    [419, 'This page has expired'],
    [429, 'Too many requests'],
    [500, 'Whoops, something went wrong on our servers.'],
    [503, 'We’ll be back soon!'],
]);

it('shows the real status in large type', function (int $status) {
    $this->get("/_test/abort/{$status}")->assertSee("<div class=\"error-page__code\">{$status}</div>", false);
})->with([404, 500, 503]);

it('covers statuses without a page of their own', function (int $status, string $heading) {
    $this->get("/_test/abort/{$status}")
        ->assertStatus($status)
        ->assertSee("<div class=\"error-page__code\">{$status}</div>", false)
        ->assertSee($heading, false);
})->with([
    [405, 'This page isn&#039;t available'],
    [410, 'This page isn&#039;t available'],
    [502, 'Whoops, something went wrong on our servers.'],
    [504, 'Whoops, something went wrong on our servers.'],
]);

it('renders 404 for an unknown URL, as the legacy fallback route did', function () {
    $this->get('/no-such-page')->assertNotFound()->assertSee('This page isn&#039;t available', false);
});

it('renders 500 for an uncaught exception without leaking it', function () {
    $this->get('/_test/crash')
        ->assertStatus(500)
        ->assertSee('Whoops, something went wrong on our servers.', false)
        ->assertDontSee('boom')
        ->assertDontSee('RuntimeException');
});

it('renders 500 when the database is unreachable', function () {
    config(['database.connections.mariadb.host' => '127.0.0.1', 'database.connections.mariadb.port' => 1]);
    DB::purge('mariadb');

    $this->get('/_test/query')
        ->assertStatus(500)
        ->assertSee('Whoops, something went wrong on our servers.', false)
        ->assertSee('id="footer"', false)
        ->assertDontSee('SQLSTATE');
});

it('keeps the security headers on error responses', function () {
    $this->get('/_test/abort/500')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('Content-Security-Policy');
});

it('answers JSON requests with JSON, not the page', function () {
    $this->getJson('/_test/abort/404')->assertNotFound()->assertJsonStructure(['message']);
});

it('links home from every error page', function () {
    $this->get('/_test/abort/404')->assertSee('href="/home" class="error-page__action"', false);
});
