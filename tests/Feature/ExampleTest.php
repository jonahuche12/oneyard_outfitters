<?php

test('the public home page is available to guests', function () {
    $this->get('/')
        ->assertOk()
        ->assertViewIs('welcome');
});
