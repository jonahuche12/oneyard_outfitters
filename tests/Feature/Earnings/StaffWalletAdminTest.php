<?php

namespace Tests\Feature\Earnings;

use App\Models\Permission;
use App\Models\Role;
use App\Models\StaffWalletEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffWalletAdminTest extends TestCase
{
    use RefreshDatabase;

    private function userWith(array $permissions, bool $active = true): User
    {
        $user = User::factory()->create(['is_active' => $active]);
        $role = Role::factory()->create(['name' => 'WA Role ' . uniqid(), 'slug' => 'wa-role-' . uniqid()]);
        foreach ($permissions as $slug) {
            $role->permissions()->attach(Permission::firstOrCreate(['slug' => $slug], ['name' => $slug]));
        }
        $user->roles()->attach($role);

        return $user;
    }

    private function finance(): User
    {
        return $this->userWith(['earnings.wallets.view', 'earnings.adjustments.create']);
    }

    private function entry(User $user, string $type = 'earning', string $amount = '1000.00'): StaffWalletEntry
    {
        return StaffWalletEntry::query()->create(['user_id' => $user->id, 'type' => $type, 'amount' => $amount, 'reason' => 'Seed entry for tests.']);
    }

    public function test_guest_redirected_and_unpermitted_forbidden(): void
    {
        $staff = User::factory()->create();

        $this->get(route('earnings.wallets.index'))->assertRedirect(route('login'));
        $plain = $this->userWith(['orders.view']);
        $this->actingAs($plain)->get(route('earnings.wallets.index'))->assertForbidden();
        $this->actingAs($plain)->get(route('earnings.wallets.show', $staff))->assertForbidden();
        $this->actingAs($plain)->post(route('earnings.wallets.adjust', $staff), ['amount' => '5', 'adjust_reason' => 'Valid reason here.'])->assertForbidden();
    }

    public function test_list_shows_balances_and_labels_inactive_staff_who_hold_money(): void
    {
        $active = User::factory()->create(['is_active' => true, 'name' => 'Active Ada']);
        $owed = User::factory()->create(['is_active' => false, 'name' => 'Owed Olu']);
        User::factory()->create(['is_active' => false, 'name' => 'Gone Gbenga']);
        $this->entry($active, 'earning', '1234.50');
        $this->entry($owed, 'earning', '500.00');

        $this->actingAs($this->finance())
            ->get(route('earnings.wallets.index'))
            ->assertOk()
            ->assertSee('Active Ada')->assertSee('₦1,234.50')
            ->assertSee('Owed Olu')->assertSee('Inactive')
            ->assertDontSee('Gone Gbenga');
    }

    public function test_list_search_filters_by_name(): void
    {
        User::factory()->create(['is_active' => true, 'name' => 'Findable Fola']);
        User::factory()->create(['is_active' => true, 'name' => 'Other Obi']);

        $this->actingAs($this->finance())
            ->get(route('earnings.wallets.index', ['q' => 'Findable']))
            ->assertOk()->assertSee('Findable Fola')->assertDontSee('Other Obi');
    }

    public function test_show_lists_entries_and_marks_reversed_ones(): void
    {
        $staff = User::factory()->create();
        $finance = $this->finance();
        $earning = $this->entry($staff);

        $this->actingAs($finance)->post(route('earnings.wallets.reverse', [$staff, $earning]), ['reverse_reason' => 'Order refunded after delivery.']);

        $this->actingAs($finance)
            ->get(route('earnings.wallets.show', $staff))
            ->assertOk()
            ->assertSee('Seed entry for tests.')
            ->assertSee('data-reversed', false)
            ->assertSee('₦0.00');
    }

    public function test_finance_adjusts_a_wallet(): void
    {
        $staff = User::factory()->create();

        $this->actingAs($this->finance())
            ->post(route('earnings.wallets.adjust', $staff), ['amount' => '-250.50', 'adjust_reason' => 'Refund recovery for disputed order.'])
            ->assertRedirect(route('earnings.wallets.show', $staff))
            ->assertSessionHas('success');

        $this->assertSame('-250.50', StaffWalletEntry::balanceFor($staff));
    }

    public function test_adjustment_errors_come_back_on_the_form_fields(): void
    {
        $staff = User::factory()->create();
        $back = route('earnings.wallets.show', $staff);
        $finance = $this->finance();

        $this->actingAs($finance)->from($back)
            ->post(route('earnings.wallets.adjust', $staff), ['amount' => '10', 'adjust_reason' => 'short'])
            ->assertRedirect($back)->assertSessionHasErrors('adjust_reason');

        $this->actingAs($finance)->from($back)
            ->post(route('earnings.wallets.adjust', $staff), ['amount' => '0', 'adjust_reason' => 'Valid reason given here.'])
            ->assertRedirect($back)->assertSessionHasErrors('amount');

        $this->assertSame(0, StaffWalletEntry::query()->count());
    }

    public function test_finance_cannot_adjust_or_reverse_their_own_wallet(): void
    {
        $finance = $this->finance();
        $own = $this->entry($finance);

        $this->actingAs($finance)
            ->post(route('earnings.wallets.adjust', $finance), ['amount' => '100', 'adjust_reason' => 'Giving myself money.'])
            ->assertSessionHasErrors('wallet');
        $this->actingAs($finance)
            ->post(route('earnings.wallets.reverse', [$finance, $own]), ['reverse_reason' => 'Reversing my own entry.'])
            ->assertSessionHasErrors('wallet');

        $this->assertSame(1, StaffWalletEntry::query()->count());
        $this->actingAs($finance)->get(route('earnings.wallets.show', $finance))
            ->assertOk()->assertDontSee('data-reverse-entry', false);
    }

    public function test_commission_entry_is_reversible_once_and_wrong_owner_is_404(): void
    {
        $staff = User::factory()->create();
        $other = User::factory()->create();
        $commission = $this->entry($staff, 'commission', '1050.00');
        $finance = $this->finance();
        $url = route('earnings.wallets.reverse', [$staff, $commission]);

        $this->actingAs($finance)->post(route('earnings.wallets.reverse', [$other, $commission]), ['reverse_reason' => 'Wrong wallet in the url.'])->assertNotFound();
        $this->actingAs($finance)->post($url, ['reverse_reason' => 'Delivery was rejected later.'])->assertSessionHas('success');
        $this->actingAs($finance)->post($url, ['reverse_reason' => 'Second attempt to reverse.'])->assertSessionHasErrors('entry');

        $this->assertSame('0.00', StaffWalletEntry::balanceFor($staff));
        $this->assertSame(2, StaffWalletEntry::query()->count());
    }

    public function test_nav_link_visible_only_with_permission(): void
    {
        $this->actingAs($this->finance())->get(route('dashboard'))
            ->assertOk()->assertSee(route('earnings.wallets.index'), false);
        $this->actingAs(User::factory()->create(['is_active' => true]))->get(route('dashboard'))
            ->assertOk()->assertDontSee(route('earnings.wallets.index'), false);
    }
}
