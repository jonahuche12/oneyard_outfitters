<?php

namespace Tests\Feature\Earnings;

use App\Models\StaffWalletEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MyWalletTest extends TestCase
{
    use RefreshDatabase;

    private function entry(User $user, string $type, string $amount, ?string $reason = null): StaffWalletEntry
    {
        return StaffWalletEntry::query()->create([
            'user_id' => $user->id,
            'type' => $type,
            'amount' => $amount,
            'reason' => $reason,
        ]);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('wallet.index'))->assertRedirect(route('login'));
    }

    public function test_inactive_user_is_redirected_to_login(): void
    {
        $user = User::factory()->create(['is_active' => false]);

        $this->actingAs($user)->get(route('wallet.index'))->assertRedirect(route('login'));
    }

    public function test_staff_sees_their_balance_and_entries(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $this->entry($user, StaffWalletEntry::TYPE_EARNING, '1000.00');
        $this->entry($user, StaffWalletEntry::TYPE_ADJUSTMENT, '-250.50', 'Refund recovery for disputed order.');

        $this->actingAs($user)
            ->get(route('wallet.index'))
            ->assertOk()
            ->assertSee('₦749.50')
            ->assertSee('Refund recovery for disputed order.')
            ->assertSee('-₦250.50')
            ->assertSee('₦1,000.00');
    }

    public function test_staff_never_sees_other_staff_entries(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $other = User::factory()->create(['is_active' => true]);
        $this->entry($user, StaffWalletEntry::TYPE_EARNING, '1234.56');
        $this->entry($other, StaffWalletEntry::TYPE_EARNING, '7777.77', 'Other staff private note.');

        $this->actingAs($user)
            ->get(route('wallet.index'))
            ->assertOk()
            ->assertSee('1,234.56')
            ->assertDontSee('7,777.77')
            ->assertDontSee('Other staff private note.');
    }

    public function test_user_id_in_the_request_is_ignored(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $other = User::factory()->create(['is_active' => true]);
        $this->entry($other, StaffWalletEntry::TYPE_EARNING, '7777.77');

        $this->actingAs($user)
            ->get(route('wallet.index', ['user_id' => $other->id, 'user' => $other->id]))
            ->assertOk()
            ->assertDontSee('7,777.77');
    }

    public function test_empty_wallet_shows_empty_state_and_zero_balance(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($user)
            ->get(route('wallet.index'))
            ->assertOk()
            ->assertSee('No wallet entries yet.')
            ->assertSee('₦0.00');
    }

    public function test_negative_balance_shows_recovery_note(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $this->entry($user, StaffWalletEntry::TYPE_ADJUSTMENT, '-100.00', 'Recovery from future earnings.');

        $this->actingAs($user)
            ->get(route('wallet.index'))
            ->assertOk()
            ->assertSee('-₦100.00')
            ->assertSee('recovered from your future earnings');
    }
}
