<?php

use Illuminate\Support\Facades\Route;

/*
 * Public routes, added as pages are ported. docs/port-ledger.json lists every
 * public route of the legacy site and what happens to it here.
 */

// Legacy homepage_lb(): on the hcsyria host, / is a 302 to /home.
Route::redirect('/', '/home', 302);

// Legacy /about answered 500 (its view was never committed); nothing links to it.
Route::permanentRedirect('/about', '/who-we-are');

Route::livewire('/projects/micro-projects', 'micro-projects')
    ->name('projects.micro');
