<?php

namespace Tests\Feature\Earnings;

use App\Actions\Earnings\ConfirmWithdrawalOtp;
use App\Actions\Earnings\IssueWithdrawalOtp;
use App\Mail\WithdrawalOtpCode;
use App\Mail\WithdrawalRequestReceived;
use App\Models\StaffPayoutAccount;
use App\Models\StaffWalletEntry;
use App\Models\StaffWithdrawal;
use App\Models\User;
use App\Models\WithdrawalOtp;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class WithdrawalOtpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    private function staff(string $balance = '10000.00', array $attributes = []): User
    {
        $user = User::factory()->create(array_merge(['is_active' => true], $attributes));

        StaffWalletEntry::query()->create(['user_id' => $user->id, 'type' => 'earning', 'amount' => $balance]);
        $this->account($user);

        return $user;
    }

    private function account(User $user): StaffPayoutAccount
    {
        return StaffPayoutAccount::query()->create([
            'user_id' => $user->id, 'bank_name' => 'Guaranty Trust Bank', 'bank_code' => '058',
            'account_name' => 'Ada Obi', 'account_number' => '0123456789', 'last_four' => '6789',
            'is_active' => true, 'usable_from' => now()->subHour(),
        ]);
    }

    /** Issues a code and returns it, read from the (faked) email. */
    private function issue(User $user, string $amount = '2000'): string
    {
        app(IssueWithdrawalOtp::class)->execute($user, $amount);

        $code = null;
        Mail::assertSent(WithdrawalOtpCode::class, function ($mail) use (&$code) {
            $code = $mail->code;

            return true;
        });

        return $code;
    }

    private function confirm(User $user, string $code): StaffWithdrawal
    {
        return app(ConfirmWithdrawalOtp::class)->execute($user, $code);
    }

    private function assertInvalid(callable $fn, string $key, ?string $contains = null): void
    {
        try {
            $fn();
        } catch (ValidationException $e) {
            $this->assertArrayHasKey($key, $e->errors());
            if ($contains !== null) {
                $this->assertStringContainsString($contains, $e->errors()[$key][0]);
            }

            return;
        }

        $this->fail('Expected a validation error.');
    }

    private function wrongCode(string $code): string
    {
        return $code === '000000' ? '111111' : '000000';
    }

    public function test_issue_emails_a_six_digit_code_and_stores_only_a_hash(): void
    {
        $user = $this->staff();

        $code = $this->issue($user);

        $this->assertMatchesRegularExpression('/^\d{6}$/', $code);

        $otp = WithdrawalOtp::query()->firstOrFail();
        $this->assertNotSame($code, $otp->code_hash);
        $this->assertTrue(Hash::check($code, $otp->code_hash));
        $this->assertSame(200000, $otp->amount_cents);
        $this->assertTrue($otp->expires_at->between(now()->addMinutes(9), now()->addMinutes(10)->addSecond()));
        Mail::assertSent(WithdrawalOtpCode::class, fn ($m) => $m->hasTo($user->email));
    }

    public function test_correct_code_registers_the_request_for_the_bound_amount(): void
    {
        $user = $this->staff();
        $code = $this->issue($user, '2500.50');

        $w = $this->confirm($user, $code);

        $this->assertSame('2500.50', $w->amount);
        $this->assertSame('requested', $w->status);
        $this->assertNotNull(WithdrawalOtp::query()->firstOrFail()->consumed_at);
    }

    public function test_a_code_cannot_be_used_twice(): void
    {
        $user = $this->staff();
        $code = $this->issue($user);
        $this->confirm($user, $code);

        $this->assertInvalid(fn () => $this->confirm($user, $code), 'code');
        $this->assertSame(1, StaffWithdrawal::query()->count());
    }

    public function test_wrong_codes_count_attempts_and_the_fifth_invalidates(): void
    {
        $user = $this->staff();
        $code = $this->issue($user);
        $wrong = $this->wrongCode($code);

        for ($i = 1; $i <= 4; $i++) {
            $this->assertInvalid(fn () => $this->confirm($user, $wrong), 'code', 'incorrect');
        }
        $otp = WithdrawalOtp::query()->firstOrFail();
        $this->assertSame(4, $otp->attempts);
        $this->assertNull($otp->invalidated_at);

        $this->assertInvalid(fn () => $this->confirm($user, $wrong), 'code', 'Too many');
        $this->assertNotNull(WithdrawalOtp::query()->firstOrFail()->invalidated_at);

        $this->assertInvalid(fn () => $this->confirm($user, $code), 'code');
        $this->assertSame(0, StaffWithdrawal::query()->count());
    }

    public function test_an_expired_code_is_rejected(): void
    {
        $user = $this->staff();
        $code = $this->issue($user);

        $this->travel(11)->minutes();

        $this->assertInvalid(fn () => $this->confirm($user, $code), 'code', 'expired');
    }

    public function test_malformed_codes_are_rejected(): void
    {
        $user = $this->staff();
        $this->issue($user);

        $this->assertInvalid(fn () => $this->confirm($user, '12345'), 'code', '6-digit');
        $this->assertInvalid(fn () => $this->confirm($user, 'abcdef'), 'code', '6-digit');
    }

    public function test_resend_has_a_cooldown_and_a_new_code_supersedes_the_old_one(): void
    {
        $user = $this->staff();
        $first = $this->issue($user);

        $this->assertInvalid(fn () => $this->issue($user), 'otp', 'wait');

        $this->travel(61)->seconds();
        $second = $this->issue($user);

        if ($first !== $second) {
            $this->assertInvalid(fn () => $this->confirm($user, $first), 'code');
        }
        $this->assertSame('requested', $this->confirm($user, $second)->status);
    }

    public function test_at_most_three_codes_per_hour(): void
    {
        $user = $this->staff();

        $this->issue($user);
        $this->travel(61)->seconds();
        $this->issue($user);
        $this->travel(61)->seconds();
        $this->issue($user);
        $this->travel(61)->seconds();

        $this->assertInvalid(fn () => $this->issue($user), 'otp', 'Too many');
        $this->assertSame(3, WithdrawalOtp::query()->count());
    }

    public function test_issue_applies_the_same_rules_as_registration(): void
    {
        $user = $this->staff('10000.00');

        $this->assertInvalid(fn () => app(IssueWithdrawalOtp::class)->execute($user, '500'), 'amount', 'minimum');
        $this->assertInvalid(fn () => app(IssueWithdrawalOtp::class)->execute($user, '99999'), 'amount', 'exceeds');
        $this->assertInvalid(fn () => app(IssueWithdrawalOtp::class)->execute($user, 'abc'), 'amount');

        Mail::assertNothingSent();
        $this->assertSame(0, WithdrawalOtp::query()->count());
    }

    public function test_confirmation_rechecks_the_balance(): void
    {
        $user = $this->staff('3000.00');
        $code = $this->issue($user, '2000');

        StaffWalletEntry::query()->create(['user_id' => $user->id, 'type' => 'adjustment', 'amount' => '-2500.00']);

        $this->assertInvalid(fn () => $this->confirm($user, $code), 'amount', 'exceeds');
        $this->assertSame(0, StaffWithdrawal::query()->count());
        $this->assertNull(WithdrawalOtp::query()->firstOrFail()->consumed_at);
    }

    public function test_a_changed_payout_account_invalidates_the_code(): void
    {
        $user = $this->staff();
        $code = $this->issue($user);

        StaffPayoutAccount::query()->where('user_id', $user->id)->update(['is_active' => false]);
        $this->account($user);

        $this->assertInvalid(fn () => $this->confirm($user, $code), 'code', 'changed');
        $this->assertNotNull(WithdrawalOtp::query()->firstOrFail()->invalidated_at);
        $this->assertSame(0, StaffWithdrawal::query()->count());
    }

    public function test_one_request_per_lagos_day_and_cancelled_ones_still_count(): void
    {
        $this->travelTo(Carbon::parse('2026-10-15 23:30:00', 'Africa/Lagos'));
        $user = $this->staff();

        $w = $this->confirm($user, $this->issue($user));
        $w->update(['status' => StaffWithdrawal::STATUS_CANCELLED]);

        $this->travelTo(Carbon::parse('2026-10-15 23:50:00', 'Africa/Lagos'));
        $this->assertInvalid(fn () => app(IssueWithdrawalOtp::class)->execute($user, '2000'), 'amount', 'one withdrawal request per day');
    }

    public function test_the_next_lagos_day_allows_a_new_request(): void
    {
        $this->travelTo(Carbon::parse('2026-10-15 23:30:00', 'Africa/Lagos'));
        $user = $this->staff();

        $w = $this->confirm($user, $this->issue($user));
        $w->update(['status' => StaffWithdrawal::STATUS_CANCELLED]);

        $this->travelTo(Carbon::parse('2026-10-16 00:30:00', 'Africa/Lagos'));

        $code = $this->issue($user);
        $this->assertSame('requested', $this->confirm($user, $code)->status);
        $this->assertSame(2, StaffWithdrawal::query()->count());
    }

    public function test_withdrawal_otp_is_email_only_even_when_whatsapp_is_enabled(): void
    {
        config([
            'services.whatsapp.enabled' => true,
            'services.whatsapp.token' => 'test-token',
            'services.whatsapp.phone_number_id' => '12345',
        ]);
        Http::fake();

        $user = $this->staff('10000.00', ['phone' => '+2348012345678']);
        $result = app(IssueWithdrawalOtp::class)->execute($user, '2000');

        $this->assertSame(['email'], $result['channels']);
        $this->assertSame(1, WithdrawalOtp::query()->count());
        Http::assertNothingSent();
        Mail::assertSent(WithdrawalOtpCode::class);
    }

    public function test_email_failure_does_not_fall_back_to_whatsapp_or_issue_a_code(): void
    {
        config([
            'services.whatsapp.enabled' => true,
            'services.whatsapp.token' => 'test-token',
            'services.whatsapp.phone_number_id' => '12345',
        ]);
        Http::fake();

        $user = $this->staff('10000.00', ['phone' => '+2348012345678']);

        Mail::shouldReceive('to')
            ->once()
            ->with($user->email)
            ->andThrow(new \RuntimeException('Simulated email delivery failure'));

        $this->assertInvalid(
            fn () => app(IssueWithdrawalOtp::class)->execute($user, '2000'),
            'otp',
            'could not deliver'
        );

        $this->assertSame(0, WithdrawalOtp::query()->count());
        Http::assertNothingSent();
    }



    public function test_withdrawal_store_only_issues_otp_and_does_not_create_a_withdrawal(): void
    {
        $user = $this->staff();

        $this->actingAs($user)
            ->post(route('wallet.withdrawals.store'), ['amount' => '2000'])
            ->assertRedirect(route('wallet.withdrawals'))
            ->assertSessionHas('success');

        $this->assertSame(0, StaffWithdrawal::query()->count());
        $this->assertSame(1, WithdrawalOtp::query()->count());
        Mail::assertSent(WithdrawalOtpCode::class, fn ($mail) => $mail->hasTo($user->email));
    }

    public function test_withdrawal_route_creates_request_only_after_email_otp_confirmation(): void
    {
        $user = $this->staff();
        $this->actingAs($user)
            ->post(route('wallet.withdrawals.store'), ['amount' => '2000'])
            ->assertRedirect(route('wallet.withdrawals'));

        $this->assertSame(0, StaffWithdrawal::query()->count());

        $code = null;
        Mail::assertSent(WithdrawalOtpCode::class, function ($mail) use (&$code) {
            $code = $mail->code;
            return true;
        });

        $this->from(route('wallet.withdrawals'))
            ->post(route('wallet.withdrawals.confirm'), ['code' => $this->wrongCode($code)])
            ->assertRedirect(route('wallet.withdrawals'))
            ->assertSessionHasErrors('code');

        $this->assertSame(0, StaffWithdrawal::query()->count());
        Mail::assertNotSent(WithdrawalRequestReceived::class);

        $this->post(route('wallet.withdrawals.confirm'), ['code' => $code])
            ->assertRedirect(route('wallet.withdrawals'))
            ->assertSessionHas('success');

        $this->assertSame(1, StaffWithdrawal::query()->count());

        Mail::assertSent(
            WithdrawalRequestReceived::class,
            fn ($mail) => $mail->hasTo($user->email)
                && $mail->name === $user->name
                && $mail->amount === '2000.00'
                && $mail->walletUrl === route('wallet.index')
        );
        Mail::assertSent(WithdrawalRequestReceived::class, 1);
    }

    public function test_new_payout_account_must_complete_waiting_period_before_otp_or_withdrawal(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        StaffWalletEntry::query()->create([
            'user_id' => $user->id,
            'type' => 'earning',
            'amount' => '10000.00',
        ]);

        $account = StaffPayoutAccount::query()->create([
            'user_id' => $user->id,
            'bank_name' => 'Guaranty Trust Bank',
            'bank_code' => '058',
            'account_name' => 'Ada Obi',
            'account_number' => '0123456789',
            'last_four' => '6789',
            'is_active' => true,
            'usable_from' => now()->addHours(24),
        ]);

        $this->actingAs($user)
            ->post(route('wallet.withdrawals.store'), ['amount' => '2000'])
            ->assertSessionHasErrors('account');

        $this->assertSame(0, WithdrawalOtp::query()->count());
        $this->assertSame(0, StaffWithdrawal::query()->count());
        Mail::assertNothingSent();

        $account->update(['usable_from' => now()->subMinute()]);

        $this->post(route('wallet.withdrawals.store'), ['amount' => '2000'])
            ->assertRedirect(route('wallet.withdrawals'))
            ->assertSessionHas('success');

        $this->assertSame(0, StaffWithdrawal::query()->count());
        $this->assertSame(1, WithdrawalOtp::query()->count());

        $code = null;
        Mail::assertSent(WithdrawalOtpCode::class, function ($mail) use (&$code, $user) {
            $code = $mail->code;

            return $mail->hasTo($user->email);
        });

        $this->post(route('wallet.withdrawals.confirm'), ['code' => $code])
            ->assertRedirect(route('wallet.withdrawals'))
            ->assertSessionHas('success');

        $withdrawal = StaffWithdrawal::query()->sole();

        $this->assertSame('2000.00', $withdrawal->amount);
        $this->assertSame($account->id, $withdrawal->payout_account_id);
        $this->assertNotNull(WithdrawalOtp::query()->sole()->consumed_at);
    }

}
