<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

const RESET_STATUS_MESSAGE =
    'If an active staff account matches that email address, a password reset link will be sent.';

it('renders the forgot-password page', function () {
    $this->get('/forgot-password')
        ->assertOk()
        ->assertViewIs('auth.forgot-password')
        ->assertSee('email');
});

it('renders the reset-password page with its token', function () {
    $this->get('/reset-password/example-token?email=staff%40oneyard.test')
        ->assertOk()
        ->assertViewIs('auth.reset-password')
        ->assertSee('example-token')
        ->assertSee('staff@oneyard.test')
        ->assertSee('password_confirmation');
});

it('sends a reset notification to an active user with a generic response', function () {
    Notification::fake();

    $user = User::factory()->create([
        'email' => 'staff@oneyard.test',
        'is_active' => true,
    ]);

    $this->from('/forgot-password')
        ->post('/forgot-password', ['email' => $user->email])
        ->assertRedirect('/forgot-password')
        ->assertSessionHas('status', RESET_STATUS_MESSAGE);

    Notification::assertSentTo($user, ResetPassword::class);
});

it('returns the same generic web response for unknown and inactive accounts', function () {
    Notification::fake();

    User::factory()->create([
        'email' => 'inactive@oneyard.test',
        'is_active' => false,
    ]);

    foreach ([
        'unknown@oneyard.test',
        'inactive@oneyard.test',
    ] as $email) {
        $this->from('/forgot-password')
            ->post('/forgot-password', ['email' => $email])
            ->assertRedirect('/forgot-password')
            ->assertSessionHas('status', RESET_STATUS_MESSAGE);
    }

    Notification::assertNothingSent();
});

it('returns the same JSON response for active and unknown accounts', function () {
    Notification::fake();

    $user = User::factory()->create([
        'email' => 'staff@oneyard.test',
        'is_active' => true,
    ]);

    $activeResponse = $this->postJson('/forgot-password', [
        'email' => $user->email,
    ]);

    $unknownResponse = $this->postJson('/forgot-password', [
        'email' => 'unknown@oneyard.test',
    ]);

    $activeResponse
        ->assertOk()
        ->assertExactJson(['message' => RESET_STATUS_MESSAGE]);

    $unknownResponse
        ->assertOk()
        ->assertExactJson(['message' => RESET_STATUS_MESSAGE]);

    Notification::assertSentTo($user, ResetPassword::class);
});

it('still rejects malformed email addresses', function () {
    $this->postJson('/forgot-password', [
        'email' => 'not-an-email',
    ])->assertUnprocessable()
      ->assertJsonValidationErrors('email');
});

it('allows an active user to reset their password with a valid token', function () {
    Notification::fake();

    $user = User::factory()->create([
        'email' => 'staff@oneyard.test',
        'password' => 'old-password',
        'is_active' => true,
    ]);

    $this->post('/forgot-password', [
        'email' => $user->email,
    ]);

    $token = null;

    Notification::assertSentTo(
        $user,
        ResetPassword::class,
        function (ResetPassword $notification) use (&$token): bool {
            $token = $notification->token;

            return true;
        }
    );

    expect($token)->not->toBeNull();

    $this->post('/reset-password', [
        'token' => $token,
        'email' => $user->email,
        'password' => 'new-secure-password',
        'password_confirmation' => 'new-secure-password',
    ])
        ->assertRedirect('/login')
        ->assertSessionHas('status');

    $user->refresh();

    expect(Hash::check('new-secure-password', $user->password))->toBeTrue()
        ->and(Hash::check('old-password', $user->password))->toBeFalse();
});

it('rejects an invalid reset token without changing the password', function () {
    $user = User::factory()->create([
        'email' => 'staff@oneyard.test',
        'password' => 'old-password',
        'is_active' => true,
    ]);

    $this->from('/reset-password/invalid-token')
        ->post('/reset-password', [
            'token' => 'invalid-token',
            'email' => $user->email,
            'password' => 'new-secure-password',
            'password_confirmation' => 'new-secure-password',
        ])
        ->assertRedirect('/reset-password/invalid-token')
        ->assertSessionHasErrors();

    expect(Hash::check('old-password', $user->fresh()->password))->toBeTrue();
});

it('rejects an expired reset token without changing the password', function () {
    Notification::fake();

    $user = User::factory()->create([
        'email' => 'staff@oneyard.test',
        'password' => 'old-password',
        'is_active' => true,
    ]);

    $this->post('/forgot-password', ['email' => $user->email]);

    $token = null;

    Notification::assertSentTo(
        $user,
        ResetPassword::class,
        function (ResetPassword $notification) use (&$token): bool {
            $token = $notification->token;

            return true;
        }
    );

    expect($token)->not->toBeNull();

    Carbon::setTestNow(now()->addMinutes(11));

    try {
        $this->from('/reset-password/'.$token)
            ->post('/reset-password', [
                'token' => $token,
                'email' => $user->email,
                'password' => 'new-secure-password',
                'password_confirmation' => 'new-secure-password',
            ])
            ->assertRedirect('/reset-password/'.$token)
            ->assertSessionHasErrors();
    } finally {
        Carbon::setTestNow();
    }

    expect(Hash::check('old-password', $user->fresh()->password))->toBeTrue();
});

it('rejects password resets for inactive users even if a token exists', function () {
    $user = User::factory()->create([
        'email' => 'inactive@oneyard.test',
        'password' => 'old-password',
        'is_active' => false,
    ]);

    DB::table('password_reset_tokens')->insert([
        'email' => $user->email,
        'token' => Hash::make('inactive-reset-token'),
        'created_at' => now(),
    ]);

    $this->from('/reset-password/inactive-reset-token')
        ->post('/reset-password', [
            'token' => 'inactive-reset-token',
            'email' => $user->email,
            'password' => 'new-secure-password',
            'password_confirmation' => 'new-secure-password',
        ])
        ->assertRedirect('/reset-password/inactive-reset-token')
        ->assertSessionHasErrors();

    expect(Hash::check('old-password', $user->fresh()->password))->toBeTrue();
});
