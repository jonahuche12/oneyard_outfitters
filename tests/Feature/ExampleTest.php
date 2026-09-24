<?php

test('the application redirects the root URL to the dashboard', function () {
    $response = $this->get('/');

    $response->assertRedirect('/dashboard');
});