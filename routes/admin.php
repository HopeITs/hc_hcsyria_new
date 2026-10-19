<?php

use Illuminate\Support\Facades\Route;

/*
 * The staff dashboard, /admin/... Every route is behind sign-in and a role the
 * admin gives something to do (EnsureAdminAccess); what each person may do on a
 * page is decided by ProjectPolicy, in the page and again on every action.
 *
 * Routes are added as sections are built. The legacy address of each is kept in
 * docs/port-ledger.json (admin routes are not parity-bound: see the upgrade plan).
 */
Route::prefix('admin')->name('admin.')->middleware(['web', 'auth', 'admin.access'])->group(function (): void {
    Route::livewire('/', 'pages::admin.dashboard')->name('dashboard');

    Route::livewire('/projects', 'pages::admin.projects.index')->name('projects.index');
    Route::livewire('/projects/create', 'pages::admin.projects.form')->name('projects.create');
    Route::livewire('/projects/{project}/edit', 'pages::admin.projects.form')->whereNumber('project')->name('projects.edit');
    Route::livewire('/projects/{project}', 'pages::admin.projects.show')->whereNumber('project')->name('projects.show');
});
