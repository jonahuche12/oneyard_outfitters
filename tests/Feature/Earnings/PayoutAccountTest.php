<?php

namespace Tests\Feature\Earnings;

use App\Models\StaffPayoutAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PayoutAccountTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $o = []): array
    {
        return array_merge(['bank_code' => '058', 'account_name' => 'Ada Obi', 'account_number' => '0123456789'], $o);
    }

    public function test_guest_is_redirected(): void
    {
        $this->get(route('wallet.payout-account'))->assertRedirect(route('login'));
        $this->post(route('wallet.payout-account.store'), $this->payload())->assertRedirect(route('login'));
    }

    public function test_staff_saves_an_account_and_the_number_is_encrypted_at_rest(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($user)->post(route('wallet.payout-account.store'), $this->payload())
            ->assertRedirect(route('wallet.payout-account'))->assertSessionHas('success');

        $account = StaffPayoutAccount::query()->firstOrFail();
        $this->assertSame('0123456789', $account->account_number);
        $this->assertSame('6789', $account->last_four);
        $this->assertSame('Guaranty Trust Bank', $account->bank_name);
        $this->assertNotSame('0123456789', DB::table('staff_payout_accounts')->value('account_number'));
        $this->assertFalse($account->isUsable());
        $this->assertEqualsWithDelta(24, now()->diffInHours($account->usable_from, false), 1);
    }

    public function test_page_shows_only_the_last_four_digits_and_the_waiting_note(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $this->actingAs($user)->post(route('wallet.payout-account.store'), $this->payload());

        $this->actingAs($user)->get(route('wallet.payout-account'))
            ->assertOk()->assertSee('••••6789')->assertDontSee('0123456789')->assertSee('Usable for withdrawals from');
    }

    public function test_changing_the_account_keeps_history_and_restarts_the_wait(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $this->actingAs($user)->post(route('wallet.payout-account.store'), $this->payload());
        $this->actingAs($user)->post(route('wallet.payout-account.store'), $this->payload(['bank_code' => '057', 'account_number' => '9876543210']));

        $this->assertSame(2, StaffPayoutAccount::query()->count());
        $this->assertSame(1, StaffPayoutAccount::query()->where('is_active', true)->count());
        $this->assertSame('3210', StaffPayoutAccount::query()->where('is_active', true)->value('last_four'));
    }

    public function test_invalid_input_is_rejected(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $back = route('wallet.payout-account');

        foreach ([
            ['account_number' => '12345'],
            ['account_number' => '12345678ab'],
            ['bank_code' => '999'],
            ['account_name' => 'x'],
        ] as $bad) {
            $this->actingAs($user)->from($back)->post(route('wallet.payout-account.store'), $this->payload($bad))
                ->assertRedirect($back)->assertSessionHasErrors();
        }

        $this->assertSame(0, StaffPayoutAccount::query()->count());
    }

    public function test_user_id_in_the_request_is_ignored_and_accounts_are_private(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $other = User::factory()->create(['is_active' => true]);

        $this->actingAs($user)->post(route('wallet.payout-account.store'), $this->payload(['user_id' => $other->id]));
        $this->assertSame($user->id, StaffPayoutAccount::query()->firstOrFail()->user_id);

        $this->actingAs($other)->get(route('wallet.payout-account'))->assertOk()->assertDontSee('••••6789');
    }

    public function test_inactive_user_is_redirected(): void
    {
        $user = User::factory()->create(['is_active' => false]);

        $this->actingAs($user)->get(route('wallet.payout-account'))->assertRedirect(route('login'));
    }
}
