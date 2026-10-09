<?php

it('redirects / to /home with a 302, as the live site does', function () {
    $this->get('/')->assertStatus(302)->assertRedirect('/home');
});

it('sends the dead /about route to /who-we-are permanently', function () {
    $this->get('/about')->assertStatus(301)->assertRedirect('/who-we-are');
});

it('renders the micro-projects page', function () {
    $this->get('/projects/micro-projects')->assertOk();
});
