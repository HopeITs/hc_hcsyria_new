<?php

/*
 * Error pages, one entry per status code (resources/views/errors).
 *
 * 404 and 500 are the legacy wording (the former lang/en/404.php and 500.php).
 * 503 is the legacy maintenance page (public/indexM.php). The others had no
 * page of their own on the legacy site — Laravel's plain default showed — and
 * are written in the same voice.
 */

return [

    401 => [
        'title' => 'Error 401',
        'heading' => 'Please sign in',
        'message' => 'You need to be signed in to view this page.',
    ],

    403 => [
        'title' => 'Error 403',
        'heading' => 'This page isn\'t available to you',
        'message' => 'You don\'t have permission to view it.',
    ],

    404 => [
        'title' => 'Error 404',
        'heading' => 'This page isn\'t available',
        'message' => 'The link you followed may be broken.',
    ],

    419 => [
        'title' => 'Error 419',
        'heading' => 'This page has expired',
        'message' => 'Please go back, refresh the page and try again.',
    ],

    429 => [
        'title' => 'Error 429',
        'heading' => 'Too many requests',
        'message' => 'Please wait a moment and try again.',
    ],

    500 => [
        'title' => 'Error 500',
        'heading' => 'Whoops, something went wrong on our servers.',
        'message' => null,
    ],

    503 => [
        'title' => 'Site Maintenance',
        'heading' => 'We’ll be back soon!',
        'message' => 'Sorry for the inconvenience but we’re performing some maintenance at the moment. If you need to you can always contact us, otherwise we’ll be back online shortly!',
    ],

    '4xx' => [
        'title' => 'Error',
        'heading' => 'This page isn\'t available',
        'message' => 'The request could not be completed.',
    ],

    '5xx' => [
        'title' => 'Error',
        'heading' => 'Whoops, something went wrong on our servers.',
        'message' => null,
    ],

    'back_home' => 'Back to Homepage',

];
