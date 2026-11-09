<?php

use App\Http\Controllers\ServeMedia;
use Illuminate\Support\Facades\Route;

/*
 * Public routes, added as pages are ported. docs/port-ledger.json lists every
 * public route of the legacy site and what happens to it here.
 */

// Legacy homepage_lb(): on the hcsyria host, / is a 302 to /home.
Route::redirect('/', '/home', 302);

Route::livewire('/home', 'pages::home')
    ->name('home');

// The legacy router sent both of these to the same page.
Route::livewire('/who-we-are', 'pages::who-we-are')->name('public.who-we-are');
Route::livewire('/who-we-are/partners', 'pages::who-we-are')->name('public.who-we-are-partners');

Route::livewire('/projects/disc', 'pages::disc')->name('public.disc_home');

Route::livewire('/projects/study-zone', 'pages::study-zone')->name('public.studyzone2');

Route::livewire('/projects/school-to-school', 'pages::school-to-school')->name('new.public.school2school');

Route::livewire('/projects/childrenuni', 'pages::children-university')->name('public.children_uni');

Route::livewire('/projects/leap', 'pages::leap')->name('public.leap');

Route::livewire('/search', 'pages::search')->name('search');

Route::livewire('/christianyouth', 'pages::christian-youth')->name('public.youth');

Route::livewire('/rootsofhope', 'pages::roots-of-hope')->name('roots');

Route::livewire('/rootsofhope/{id}', 'pages::roots-of-hope-reader')->whereNumber('id')->name('roots.read');

Route::livewire('/our-news', 'pages::our-news')->name('new.public.newsletter');

// One story. Only the id picks it; the city (a slug, so it may hold hyphens) and the date (eight digits) are as the list links them.
Route::livewire('/our-news/{id}-{city}-{date}', 'pages::news-article')
    ->whereNumber('id')
    ->where('city', '[A-Za-z0-9-]+')
    ->where('date', '[0-9]{8}')
    ->name('new.public.article');

// The sign-in page. Fortify's POST /login (config/fortify.php: views off) takes the form; a signed-in account is sent on.
Route::view('/login', 'auth.login')->middleware('guest')->name('login');

Route::livewire('/projects/family-to-family', 'pages::family-to-family')->name('new.public.family2family');

Route::livewire('/projects/micro-projects', 'pages::micro-projects')
    ->name('new.public.micro_project_test');

// One activity. The id is digits only, so that a title with a hyphen in it cannot be read as part of the id.
Route::livewire('/projects/{type}/{id}-{slug}', 'pages::initiative')
    ->whereNumber('id')
    ->name('new.public.initiative');

// A category's activities: social activities, rehabilitation, relief, earthquake response.
// After every programme page above, which this would otherwise swallow.
Route::livewire('/projects/{type}', 'pages::project-category')->name('public.initiatives.type');

// Images, files and video, from MEDIA_ROOT, at the URLs the legacy site used.
Route::get('/{area}/{path}', ServeMedia::class)
    ->where('area', 'images|files|video')
    ->where('path', '.*')
    ->name('media');
