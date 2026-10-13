<?php

/*
 * Sign-in only. Accounts are made by an administrator, so there is no
 * registration, password reset, email verification or two-factor feature
 * here; the members' sign-in form is the gate on /projects/micro-projects
 * and Fortify draws no pages of its own. The admin sign-in comes with the
 * admin phase.
 */
return [
    'guard' => 'web',
    'passwords' => 'users',
    'username' => 'email',
    'email' => 'email',
    'lowercase_usernames' => true,
    'home' => '/home',
    'prefix' => '',
    'domain' => null,
    'middleware' => ['web'],
    'limiters' => [
        'login' => 'login',
    ],
    'views' => false,
    'features' => [],
];
