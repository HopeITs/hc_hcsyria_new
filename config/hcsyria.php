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
    | outside public/. Content, not code: it is not in the repository.
    |
    */

    'media_root' => env('MEDIA_ROOT'),

];
