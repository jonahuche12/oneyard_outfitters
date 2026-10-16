<?php

namespace Tests\Feature\Earnings;

use App\Models\StaffPayoutAccount;
use App\Models\StaffWalletEntry;
use App\Models\StaffWithdrawal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WithdrawalRequestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \Illuminate\Support\Facades\Mail::fake();
    }

    private function staff(string $balance = '10000.00', bool $account = true, bool $usable = true): User
    {
        $user = User::factory()->create(['is_active' => true]);

        if ($balance !== '0') {
            StaffWalletEntry::query()->create(['user_id' => $user->id, 'type' => 'earning', 'amount' => $balance]);
        }

        if ($account) {
            $this->account($user, $usable);
        }

        return $user;
    }

    private function account(User $user, bool $usable = true): StaffPayoutAccount
    {
        return StaffPayoutAccount::query()->create([
            'user_id' => $user->id, 'bank_name' => 'Guaranty Trust Bank', 'bank_code' => '058',
            'account_name' => 'Ada Obi', 'account_number' => '0123456789', 'last_four' => '6789',
            'is_active' => true, 'usable_from' => $usable ? now()->subHour() : now()->addHours(5),
        ]);
    }

    private function request(User $user, string $amount, array $extra = [])
    {
        $sentBefore = \Illuminate\Support\Facades\Mail::sent(\App\Mail\WithdrawalOtpCode::class)->count();

        $response = $this->actingAs($user)->from(route('wallet.withdrawals'))
            ->post(route('wallet.withdrawals.store'), array_merge(['amount' => $amount], $extra));

        // Complete the OTP flow in these workflow tests. Dedicated OTP tests
        // independently verify that initiation alone creates no withdrawal.
        if ($response->getSession()->has('success')
            && str_contains((string) $response->getSession()->get('success'), 'emailed')) {
            $sent = \Illuminate\Support\Facades\Mail::sent(\App\Mail\WithdrawalOtpCode::class);

            $this->assertGreaterThan(
                $sentBefore,
                $sent->count(),
                'A successful withdrawal initiation must email an OTP.'
            );

            $latestOtp = $sent->last();

            $this->assertNotNull($latestOtp, 'The withdrawal OTP email must exist.');

            return $this->actingAs($user)->from(route('wallet.withdrawals'))
                ->post(route('wallet.withdrawals.confirm'), [
                    'code' => $latestOtp->code,
                ]);
        }

        return $response;
    }

    public function test_guest_is_redirected(): void
    {
        $this->get(route('wallet.withdrawals'))->assertRedirect(route('login'));
        $this->post(route('wallet.withdrawals.store'), ['amount' => '1000'])->assertRedirect(route('login'));
    }

    public function test_staff_requests_a_withdrawal_with_an_account_snapshot(): void
    {
        $user = $this->staff('10000.00');

        $this->request($user, '2500.50')->assertRedirect(route('wallet.withdrawals'))->assertSessionHas('success');

        $w = StaffWithdrawal::query()->firstOrFail();
        $this->assertSame('2500.50', $w->amount);
        $this->assertSame('requested', $w->status);
        $this->assertSame('Guaranty Trust Bank', $w->bank_name);
        $this->assertSame('6789', $w->account_last_four);
        $this->assertSame(1, StaffWalletEntry::query()->count(), 'No ledger entry until paid.');
    }

    public function test_available_balance_excludes_open_requests(): void
    {
        $user = $this->staff('10000.00');
        $this->request($user, '4000');

        $this->assertSame('6000.00', StaffWithdrawal::available($user->id));
        $this->actingAs($user)->get(route('wallet.withdrawals'))->assertOk()->assertSee('₦6,000.00');
        $this->actingAs($user)->get(route('wallet.index'))->assertOk()->assertSee('₦6,000.00');
    }

    public function test_minimum_and_available_limits_are_enforced(): void
    {
        $user = $this->staff('5000.00');

        $this->request($user, '999.99')->assertSessionHasErrors('amount');
        $this->request($user, '5000.01')->assertSessionHasErrors('amount');
        $this->request($user, 'abc')->assertSessionHasErrors('amount');
        $this->request($user, '1000.123')->assertSessionHasErrors('amount');
        $this->request($user, '5000')->assertSessionHasNoErrors();

        $this->assertSame(1, StaffWithdrawal::query()->count());
    }

    public function test_only_one_open_request_at_a_time(): void
    {
        $user = $this->staff('10000.00');

        $this->request($user, '1000')->assertSessionHasNoErrors();
        $this->request($user, '1000')->assertSessionHasErrors('amount');

        $this->assertSame(1, StaffWithdrawal::query()->count());
    }

    public function test_cancelled_and_rejected_requests_still_count_toward_the_daily_limit(): void
    {
        $this->travelTo(\Carbon\Carbon::parse('2026-10-15 10:00:00', 'Africa/Lagos'));
        $user = $this->staff('10000.00');

        $this->request($user, '1000')->assertSessionHas('success');
        $withdrawal = StaffWithdrawal::query()->firstOrFail();

        $this->actingAs($user)->post(route('wallet.withdrawals.cancel', $withdrawal))
            ->assertSessionHas('success');

        $this->request($user, '1500')->assertSessionHasErrors('amount');

        $this->travelTo(\Carbon\Carbon::parse('2026-10-16 10:00:00', 'Africa/Lagos'));
        $this->request($user, '1500')->assertSessionHas('success');

        StaffWithdrawal::query()->where('status', 'requested')->update(['status' => 'rejected']);

        $this->request($user, '2000')->assertSessionHasErrors('amount');
        $this->assertSame(2, StaffWithdrawal::query()->count());
    }

    public function test_account_must_exist_and_new_accounts_must_complete_the_waiting_period(): void
    {
        $none = $this->staff('10000.00', false);
        $waiting = $this->staff('10000.00', true, false);

        $this->request($none, '1000')->assertSessionHasErrors('account');
        $this->request($waiting, '1000')->assertSessionHasErrors('account');

        $this->assertSame(0, StaffWithdrawal::query()->count());
    }

    public function test_negative_ledger_balance_cannot_withdraw(): void
    {
        $user = $this->staff('0');
        StaffWalletEntry::query()->create(['user_id' => $user->id, 'type' => 'adjustment', 'amount' => '-500.00', 'reason' => 'Recovery entry.']);

        $this->request($user, '1000')->assertSessionHasErrors('amount');
    }

    public function test_staff_cancels_their_own_requested_withdrawal_only(): void
    {
        $user = $this->staff('10000.00');
        $other = $this->staff('10000.00');
        $this->request($user, '1000');
        $w = StaffWithdrawal::query()->firstOrFail();

        $this->actingAs($other)->post(route('wallet.withdrawals.cancel', $w))->assertNotFound();
        $this->assertSame('requested', $w->fresh()->status);

        $this->actingAs($user)->post(route('wallet.withdrawals.cancel', $w))->assertSessionHas('success');
        $this->assertSame('cancelled', $w->fresh()->status);
        $this->assertSame('10000.00', StaffWithdrawal::available($user->id));
    }

    public function test_an_approved_request_cannot_be_cancelled_by_staff(): void
    {
        $user = $this->staff('10000.00');
        $this->request($user, '1000');
        $w = StaffWithdrawal::query()->firstOrFail();
        $w->update(['status' => 'approved']);

        $this->actingAs($user)->from(route('wallet.withdrawals'))
            ->post(route('wallet.withdrawals.cancel', $w))->assertSessionHasErrors('withdrawal');

        $this->assertSame('approved', $w->fresh()->status);
        $this->assertSame('9000.00', StaffWithdrawal::available($user->id));
    }

    public function test_user_id_in_the_request_is_ignored_and_history_is_private(): void
    {
        $user = $this->staff('10000.00');
        $other = $this->staff('10000.00');

        $this->request($user, '1000', ['user_id' => $other->id])->assertSessionHas('success');
        $this->assertSame($user->id, StaffWithdrawal::query()->firstOrFail()->user_id);

        $this->actingAs($other)->get(route('wallet.withdrawals'))->assertOk()->assertSee('No withdrawal requests yet.');
    }

    public function test_page_prompts_for_an_account_when_none_exists(): void
    {
        $user = $this->staff('0', false);

        $this->actingAs($user)->get(route('wallet.withdrawals'))->assertOk()->assertSee('data-no-account', false);
    }
}
