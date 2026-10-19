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
    // Legacy's dashboard address was /admin/home; /admin leads there.
    Route::redirect('/', '/admin/home');
    Route::livewire('/home', 'pages::admin.dashboard')->name('dashboard');

    Route::livewire('/projects', 'pages::admin.projects.index')->name('projects.index');
    Route::livewire('/projects/create', 'pages::admin.projects.form')->name('projects.create');
    Route::livewire('/projects/{project}/edit', 'pages::admin.projects.form')->whereNumber('project')->name('projects.edit');
    Route::livewire('/projects/{project}', 'pages::admin.projects.show')->whereNumber('project')->name('projects.show');

    Route::livewire('/initiatives', 'pages::admin.initiatives.index')->name('initiatives.index');
    Route::livewire('/initiatives/create', 'pages::admin.initiatives.form')->name('initiatives.create');
    Route::livewire('/initiatives/{initiative}/edit', 'pages::admin.initiatives.form')->whereNumber('initiative')->name('initiatives.edit');
    Route::livewire('/initiatives/{initiative}', 'pages::admin.initiatives.show')->whereNumber('initiative')->name('initiatives.show');

    Route::livewire('/successes', 'pages::admin.successes.index')->name('successes.index');
    Route::livewire('/successes/create', 'pages::admin.successes.form')->name('successes.create');
    Route::livewire('/successes/{success}/edit', 'pages::admin.successes.form')->whereNumber('success')->name('successes.edit');
    Route::livewire('/successes/{success}', 'pages::admin.successes.show')->whereNumber('success')->name('successes.show');

    Route::livewire('/news', 'pages::admin.news.index')->name('news.index');
    Route::livewire('/news/create', 'pages::admin.news.form')->name('news.create');
    Route::livewire('/news/tags', 'pages::admin.news.tags')->name('news.tags');
    Route::livewire('/news/editions', 'pages::admin.news.editions')->name('news.editions');
    Route::livewire('/news/subscribers', 'pages::admin.news.subscribers')->name('news.subscribers');
    Route::livewire('/news/{news}/edit', 'pages::admin.news.form')->whereNumber('news')->name('news.edit');
    Route::livewire('/news/{news}', 'pages::admin.news.show')->whereNumber('news')->name('news.show');

    Route::livewire('/roots', 'pages::admin.roots.index')->name('roots.index');
    Route::livewire('/roots/create', 'pages::admin.roots.form')->name('roots.create');
    Route::livewire('/roots/{edition}/edit', 'pages::admin.roots.form')->whereNumber('edition')->name('roots.edit');
    Route::livewire('/roots/{edition}', 'pages::admin.roots.show')->whereNumber('edition')->name('roots.show');

    Route::livewire('/users', 'pages::admin.users.index')->name('users.index');
    Route::livewire('/users/create', 'pages::admin.users.form')->name('users.create');
    Route::livewire('/users/{user}/edit', 'pages::admin.users.form')->whereNumber('user')->name('users.edit');
});
