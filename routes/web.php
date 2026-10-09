<?php

use App\Http\Controllers\ServeMedia;
use Illuminate\Support\Facades\Route;

/*
 * Public routes, added as pages are ported. docs/port-ledger.json lists every
 * public route of the legacy site and what happens to it here.
 */

// Legacy homepage_lb(): on the hcsyria host, / is a 302 to /home.
Route::redirect('/', '/home', 302);

// Legacy /about answered 500 (its view was never committed); nothing links to it.
Route::permanentRedirect('/about', '/who-we-are');

Route::livewire('/home', 'pages::home')
    ->name('home');

Route::livewire('/projects/micro-projects', 'micro-projects')
    ->name('projects.micro');

// Images, files and video, from MEDIA_ROOT, at the URLs the legacy site used.
Route::get('/{area}/{path}', ServeMedia::class)
    ->where('area', 'images|files|video')
    ->where('path', '.*')
    ->name('media');
