<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('shows the login page', function () {
    $response = $this->get('/login');

    $response
        ->assertOk()
        ->assertViewIs('auth.login');
});

it('allows an active user to login', function () {
    $user = User::factory()->create([
        'email' => 'staff@oneyard.test',
        'password' => 'password',
        'is_active' => true,
    ]);

    $response = $this->post('/login', [
        'email' => 'staff@oneyard.test',
        'password' => 'password',
    ]);

    $response
        ->assertRedirect('/dashboard');

    $this->assertAuthenticatedAs($user);
});

it('rejects an inactive user', function () {
    $user = User::factory()->create([
        'email' => 'inactive@oneyard.test',
        'password' => 'password',
        'is_active' => false,
    ]);

    $response = $this->from('/login')->post('/login', [
        'email' => 'inactive@oneyard.test',
        'password' => 'password',
    ]);

    $response->assertRedirect('/login');

    $this->assertGuest();
});

it('protects the dashboard from guests', function () {
    $response = $this->get('/dashboard');

    $response
        ->assertRedirect('/login');
});

it('allows an authenticated active user to access the dashboard', function () {
    $user = User::factory()->create([
        'is_active' => true,
    ]);

    $response = $this
        ->actingAs($user)
        ->get('/dashboard');

    $response
        ->assertOk()
        ->assertViewIs('dashboard');
});

it('logs a user out', function () {
    $user = User::factory()->create([
        'is_active' => true,
    ]);

    $this->actingAs($user);

    $response = $this->post('/logout');

    $response->assertRedirect('/');

    $this->assertGuest();
});