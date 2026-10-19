<?php

it('redirects / to /home with a 302, as the live site does', function () {
    $this->get('/')->assertStatus(302)->assertRedirect('/home');
});
