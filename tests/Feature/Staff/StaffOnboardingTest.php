<?php

use App\Models\StaffOnboardingToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

function createOnboardingFixture(array $userOverrides = [], array $tokenOverrides = []): array
{
    $user = User::factory()->unverified()->create(array_merge([
        'email' => 'onboarding@oneyard.test',
        'is_active' => false,
        'password_set_at' => null,
    ], $userOverrides));

    $rawToken = bin2hex(random_bytes(32));

    $record = StaffOnboardingToken::create(array_merge([
        'user_id' => $user->id,
        'email' => $user->email,
        'token_hash' => hash('sha256', $rawToken),
        'expires_at' => now()->addHours(24),
        'consumed_at' => null,
    ], $tokenOverrides));

    return [$user, $record, $rawToken];
}

it('shows the password setup page for a valid onboarding token', function () {
    [, , $token] = createOnboardingFixture();

    $this->get(route('staff.onboarding.show', ['token' => $token]))
        ->assertOk()
        ->assertViewIs('auth.staff-onboarding')
        ->assertSee('onboarding@oneyard.test');
});

it('rejects invalid, expired, and consumed onboarding tokens', function () {
    [, $expiredRecord, $expiredToken] = createOnboardingFixture([
        'email' => 'expired@oneyard.test',
    ]);

    $expiredRecord->update([
        'expires_at' => now()->subMinute(),
    ]);

    $this->get(route('staff.onboarding.show', [
        'token' => $expiredToken,
    ]))->assertNotFound();

    [, $consumedRecord, $consumedToken] = createOnboardingFixture([
        'email' => 'consumed@oneyard.test',
    ]);

    $consumedRecord->update([
        'consumed_at' => now(),
    ]);

    $this->get(route('staff.onboarding.show', [
        'token' => $consumedToken,
    ]))->assertNotFound();

    $this->get(route('staff.onboarding.show', [
        'token' => 'invalid-token',
    ]))->assertNotFound();
});

it('activates the account immediately after password setup without email verification', function () {
    [$user, $record, $token] = createOnboardingFixture();

    $this->post(route('staff.onboarding.store', [
        'token' => $token,
    ]), [
        'password' => 'StrongPassword123!',
        'password_confirmation' => 'StrongPassword123!',
    ])->assertRedirect(route('login'))
      ->assertSessionHas(
          'status',
          'Password set successfully. Your account is now active. You can sign in.'
      );

    $user->refresh();

    expect($user->password_set_at)->not->toBeNull()
        ->and($user->is_active)->toBeTrue()
        ->and($user->email_verified_at)->toBeNull()
        ->and(Hash::check('StrongPassword123!', $user->password))->toBeTrue()
        ->and($record->fresh()->consumed_at)->not->toBeNull();

    $this->post(route('staff.onboarding.store', [
        'token' => $token,
    ]), [
        'password' => 'AnotherStrongPassword123!',
        'password_confirmation' => 'AnotherStrongPassword123!',
    ])->assertNotFound();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'StrongPassword123!',
    ])->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($user);
});

it('rejects password setup when the password confirmation is invalid', function () {
    [, , $token] = createOnboardingFixture([
        'email' => 'bad-confirmation@oneyard.test',
    ]);

    $this->from(route('staff.onboarding.show', ['token' => $token]))
        ->post(route('staff.onboarding.store', ['token' => $token]), [
            'password' => 'StrongPassword123!',
            'password_confirmation' => 'DifferentPassword123!',
        ])
        ->assertSessionHasErrors('password');
});
