<?php

use Illuminate\Support\Facades\Route;

/*
 * Routes are added as pages are migrated. Anything not listed here is still
 * served by the legacy application.
 */

Route::view('/', 'welcome');

Route::livewire('/projects/micro-projects', 'micro-projects')
    ->name('projects.micro');
