<?php

use App\Http\Middleware\ThrottleRequestsWithoutSession;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;

it('sends the hardening headers on every response', function () {
    $response = $this->get('/projects/micro-projects');

    $response->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
        ->assertHeader('Cross-Origin-Opener-Policy', 'same-origin')
        ->assertHeaderMissing('X-Powered-By');

    $csp = $response->headers->get('Content-Security-Policy');
    expect($csp)->toContain("default-src 'self'")
        ->toContain("object-src 'none'")
        ->toContain("frame-ancestors 'self'")
        ->toContain("form-action 'self'");
});

it('sends the headers on error responses too', function () {
    $this->get('/no-such-page')->assertNotFound()->assertHeader('X-Content-Type-Options', 'nosniff');
});

it('allows no third-party origin in the CSP', function () {
    $csp = $this->get('/projects/micro-projects')->headers->get('Content-Security-Policy');

    expect((string) $csp)->not->toMatch('#https?://#');
});

it('serves fonts from this origin only, preloading body text', function () {
    $html = $this->get('/projects/micro-projects')->getContent();

    expect($html)->not->toContain('fonts.googleapis.com')
        ->not->toContain('fonts.gstatic.com')
        ->toMatch('#<link rel="preload" href="[^"]+/lato-400-latin-[^"]+\.woff2" as="font" type="font/woff2" crossorigin>#');
});

it('redirects http to https in production, and refuses non-GET over http', function () {
    app()->detectEnvironment(fn () => 'production');

    $this->get('http://hcsyria.test/projects/micro-projects')
        ->assertStatus(301)
        ->assertRedirect('https://hcsyria.test/projects/micro-projects');

    $this->post('http://hcsyria.test/projects/micro-projects')->assertForbidden();
});

it('sends HSTS only over https in production', function () {
    app()->detectEnvironment(fn () => 'production');

    $this->get('https://hcsyria.test/projects/micro-projects')
        ->assertHeader('Strict-Transport-Security', 'max-age='.config('app.hsts_max_age'));
});

it('throttles requests that arrive without a session cookie', function () {
    $key = 'requests-without-session:127.0.0.1';
    RateLimiter::clear($key);
    foreach (range(1, ThrottleRequestsWithoutSession::MaxPerMinute) as $i) {
        RateLimiter::hit($key);
    }

    $this->get('/projects/micro-projects')->assertStatus(429)->assertHeader('Retry-After');

    RateLimiter::clear($key);
});

it('rate limits the Livewire update endpoint', function () {
    $route = collect(Route::getRoutes()->getRoutes())
        ->first(fn ($route) => $route->uri() === ltrim(Livewire::getUpdateUri(), '/'));

    expect($route)->not->toBeNull()
        ->and($route->gatherMiddleware())->toContain('throttle:livewire')->toContain('web');
});
