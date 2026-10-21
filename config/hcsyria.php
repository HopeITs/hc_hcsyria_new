<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Count page views
    |--------------------------------------------------------------------------
    |
    | Whether public pages add to the `page_views` counters (once per
    | session). Off by default: staging shares the live database, so a visit
    | to a test copy would be counted as a real one. Turn it on in production.
    |
    */

    'count_page_views' => (bool) env('COUNT_PAGE_VIEWS', false),

    /*
    |--------------------------------------------------------------------------
    | Media root
    |--------------------------------------------------------------------------
    |
    | The folder holding the site's images/, files/ and video/ — served at
    | /images, /files and /video by App\Http\Controllers\ServeMedia, from
    | outside public/. Content, not code: it is not in the repository. Unless
    | MEDIA_ROOT says otherwise it is Laravel's public disk, storage/app/public.
    |
    */

    'media_root' => env('MEDIA_ROOT') ?: storage_path('app/public'),

    /*
    |--------------------------------------------------------------------------
    | Map tiles
    |--------------------------------------------------------------------------
    |
    | The tile server behind the Leaflet maps (Study Zone's "Our Spread").
    | Legacy put its API key in the page source; here it is read from the
    | environment. The tiles' origin is allowed in the content security policy
    | (App\Http\Middleware\SecurityHeaders) and nothing else is.
    |
    */

    'map_tiles' => [
        'origin' => 'https://tiles.stadiamaps.com',
        'url' => 'https://tiles.stadiamaps.com/tiles/outdoors/{z}/{x}/{y}{r}.png',
        'key' => env('STADIA_API_KEY'),
    ],

];
