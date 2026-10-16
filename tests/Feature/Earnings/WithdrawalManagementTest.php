<?php

namespace Tests\Feature\Earnings;

use App\Mail\WithdrawalStatusUpdated;
use App\Models\Role;
use App\Models\StaffPayoutAccount;
use App\Models\StaffWalletEntry;
use App\Models\StaffWithdrawal;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class WithdrawalManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();

        app(RbacSeeder::class)->run();
    }

    private function userWithRole(string $roleSlug): User
    {
        $user = User::factory()->create(['is_active' => true]);

        $role = Role::query()->where('slug', $roleSlug)->firstOrFail();

        $user->roles()->attach($role->id);

        return $user;
    }

    private function staff(): User
    {
        return User::factory()->create(['is_active' => true]);
    }

    private function withdrawal(User $staff, string $status = 'requested'): StaffWithdrawal
    {
        $account = StaffPayoutAccount::query()->create([
            'user_id' => $staff->id,
            'bank_name' => 'Guaranty Trust Bank',
            'bank_code' => '058',
            'account_name' => 'Ada Obi',
            'account_number' => '0123456789',
            'last_four' => '6789',
            'is_active' => true,
            'usable_from' => now()->subDay(),
        ]);

        return StaffWithdrawal::query()->create([
            'user_id' => $staff->id,
            'amount' => '2500.00',
            'status' => $status,
            'payout_account_id' => $account->id,
            'bank_name' => $account->bank_name,
            'account_name' => $account->account_name,
            'account_last_four' => $account->last_four,
            'requested_at' => now(),
        ]);
    }

    public function test_finance_can_view_withdrawal_management(): void
    {
        $finance = $this->userWithRole('finance');
        $staff = $this->staff();

        $this->withdrawal($staff);

        $this->actingAs($finance)
            ->get(route('earnings.withdrawals.index'))
            ->assertOk()
            ->assertSee('Withdrawal Requests')
            ->assertSee($staff->email);
    }

    public function test_admin_and_super_admin_can_view_withdrawal_management(): void
    {
        foreach (['admin', 'super-admin'] as $roleSlug) {
            $manager = $this->userWithRole($roleSlug);

            $this->actingAs($manager)
                ->get(route('earnings.withdrawals.index'))
                ->assertOk();
        }
    }

    public function test_staff_without_management_permission_is_forbidden(): void
    {
        $staff = $this->staff();

        $this->actingAs($staff)
            ->get(route('earnings.withdrawals.index'))
            ->assertForbidden();
    }

    public function test_inactive_finance_user_is_redirected_to_login(): void
    {
        $finance = $this->userWithRole('finance');
        $finance->update(['is_active' => false]);

        $this->actingAs($finance)
            ->get(route('earnings.withdrawals.index'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors([
                'email' => 'Your account is inactive. Please contact an administrator.',
            ]);

        $this->assertGuest();
    }

    public function test_finance_can_approve_another_staff_members_request(): void
    {
        $finance = $this->userWithRole('finance');
        $staff = $this->staff();
        $withdrawal = $this->withdrawal($staff);

        $this->actingAs($finance)
            ->from(route('earnings.withdrawals.index'))
            ->post(route('earnings.withdrawals.approve', $withdrawal), [
                'approval_reason' => 'Payout details and available balance verified.',
            ])
            ->assertSessionHas('success');

        $withdrawal->refresh();

        $this->assertSame('approved', $withdrawal->status);
        $this->assertSame($finance->id, $withdrawal->reviewed_by);
        $this->assertNotNull($withdrawal->reviewed_at);
        $this->assertSame(
            'Payout details and available balance verified.',
            $withdrawal->notes
        );
        $this->assertSame(0, StaffWalletEntry::query()->count());

        Mail::assertSent(
            WithdrawalStatusUpdated::class,
            fn ($mail) => $mail->withdrawal->id === $withdrawal->id
        );
    }

    public function test_approval_requires_a_reason_and_does_not_change_status_without_one(): void
    {
        $finance = $this->userWithRole('finance');
        $staff = $this->staff();
        $withdrawal = $this->withdrawal($staff);

        $this->actingAs($finance)
            ->from(route('earnings.withdrawals.index'))
            ->post(route('earnings.withdrawals.approve', $withdrawal), [
                'approval_reason' => '',
            ])
            ->assertSessionHasErrors('approval_reason');

        $this->assertSame('requested', $withdrawal->fresh()->status);
        $this->assertNull($withdrawal->fresh()->notes);
        $this->assertSame(0, StaffWalletEntry::query()->count());
    }

    public function test_withdrawal_search_filters_by_staff_email_and_status(): void
    {
        $finance = $this->userWithRole('finance');
        $firstStaff = User::factory()->create([
            'name' => 'Ada Searchable',
            'email' => 'ada.searchable@example.com',
            'is_active' => true,
        ]);
        $secondStaff = User::factory()->create([
            'name' => 'Bola Different',
            'email' => 'bola.different@example.com',
            'is_active' => true,
        ]);

        $this->withdrawal($firstStaff, 'requested');
        $this->withdrawal($secondStaff, 'approved');

        $this->actingAs($finance)
            ->get(route('earnings.withdrawals.index', [
                'search' => 'ada.searchable@example.com',
            ]))
            ->assertOk()
            ->assertSee('ada.searchable@example.com')
            ->assertDontSee('bola.different@example.com');

        $this->actingAs($finance)
            ->get(route('earnings.withdrawals.index', [
                'status' => 'approved',
            ]))
            ->assertOk()
            ->assertSee('bola.different@example.com')
            ->assertDontSee('ada.searchable@example.com');
    }

    public function test_withdrawal_search_can_find_a_withdrawal_by_id_reference(): void
    {
        $finance = $this->userWithRole('finance');
        $firstStaff = $this->staff();
        $secondStaff = $this->staff();

        $target = $this->withdrawal($firstStaff);
        $other = $this->withdrawal($secondStaff);

        $this->actingAs($finance)
            ->get(route('earnings.withdrawals.index', [
                'search' => 'WD-'.str_pad((string) $target->id, 6, '0', STR_PAD_LEFT),
            ]))
            ->assertOk()
            ->assertSee($firstStaff->email)
            ->assertDontSee($secondStaff->email);

        $this->assertNotSame($target->id, $other->id);
    }

    public function test_requested_date_range_filters_withdrawals(): void
    {
        $finance = $this->userWithRole('finance');
        $insideStaff = $this->staff();
        $outsideStaff = $this->staff();

        $inside = $this->withdrawal($insideStaff);
        $inside->update(['requested_at' => now()->subDays(2)]);

        $outside = $this->withdrawal($outsideStaff);
        $outside->update(['requested_at' => now()->subDays(10)]);

        $this->actingAs($finance)
            ->get(route('earnings.withdrawals.index', [
                'from' => now()->subDays(3)->toDateString(),
                'to' => now()->toDateString(),
            ]))
            ->assertOk()
            ->assertSee($insideStaff->email)
            ->assertDontSee($outsideStaff->email);
    }

    public function test_requester_cannot_approve_their_own_withdrawal(): void
    {
        $requester = $this->userWithRole('finance');
        $withdrawal = $this->withdrawal($requester);

        $this->actingAs($requester)
            ->from(route('earnings.withdrawals.index'))
            ->post(route('earnings.withdrawals.approve', $withdrawal), [
                'approval_reason' => 'Payout details have been reviewed.',
            ])
            ->assertSessionHasErrors('withdrawal');

        $this->assertSame('requested', $withdrawal->fresh()->status);
        $this->assertSame(0, StaffWalletEntry::query()->count());
    }

    public function test_rejection_requires_a_reason_and_records_the_reviewer(): void
    {
        $finance = $this->userWithRole('finance');
        $staff = $this->staff();
        $withdrawal = $this->withdrawal($staff);

        $this->actingAs($finance)
            ->from(route('earnings.withdrawals.index'))
            ->post(route('earnings.withdrawals.reject', $withdrawal), [
                'reject_reason' => '',
            ])
            ->assertSessionHasErrors('reject_reason');

        $this->assertSame('requested', $withdrawal->fresh()->status);

        $this->actingAs($finance)
            ->post(route('earnings.withdrawals.reject', $withdrawal), [
                'reject_reason' => 'Payout account details need verification.',
            ])
            ->assertSessionHas('success');

        $withdrawal->refresh();

        $this->assertSame('rejected', $withdrawal->status);
        $this->assertSame(
            'Payout account details need verification.',
            $withdrawal->reject_reason
        );
        $this->assertSame($finance->id, $withdrawal->reviewed_by);
        $this->assertSame(0, StaffWalletEntry::query()->count());

        Mail::assertSent(
            WithdrawalStatusUpdated::class,
            fn ($mail) => $mail->withdrawal->id === $withdrawal->id
        );
    }

    public function test_payment_requires_a_reference_and_creates_one_negative_ledger_entry(): void
    {
        $finance = $this->userWithRole('finance');
        $staff = $this->staff();
        $withdrawal = $this->withdrawal($staff, 'approved');

        $this->actingAs($finance)
            ->from(route('earnings.withdrawals.index'))
            ->post(route('earnings.withdrawals.paid', $withdrawal), [
                'payment_reference' => '',
            ])
            ->assertSessionHasErrors('payment_reference');

        $this->assertSame('approved', $withdrawal->fresh()->status);
        $this->assertSame(0, StaffWalletEntry::query()->count());

        $this->actingAs($finance)
            ->post(route('earnings.withdrawals.paid', $withdrawal), [
                'payment_reference' => 'BANK-TRANSFER-001',
            ])
            ->assertSessionHas('success');

        $withdrawal->refresh();

        $this->assertSame('paid', $withdrawal->status);
        $this->assertSame($finance->id, $withdrawal->paid_by);
        $this->assertNotNull($withdrawal->paid_at);
        $this->assertSame('BANK-TRANSFER-001', $withdrawal->payment_reference);

        $entries = StaffWalletEntry::query()
            ->where('user_id', $staff->id)
            ->where('type', StaffWalletEntry::TYPE_WITHDRAWAL)
            ->get();

        $this->assertCount(1, $entries);
        $this->assertSame('-2500.00', $entries->first()->amount);

        Mail::assertSent(
            WithdrawalStatusUpdated::class,
            fn ($mail) => $mail->withdrawal->id === $withdrawal->id
        );
    }

    public function test_paid_withdrawal_cannot_be_paid_twice(): void
    {
        $finance = $this->userWithRole('finance');
        $staff = $this->staff();
        $withdrawal = $this->withdrawal($staff, 'approved');

        $this->actingAs($finance)->post(
            route('earnings.withdrawals.paid', $withdrawal),
            ['payment_reference' => 'BANK-TRANSFER-001']
        )->assertSessionHas('success');

        $this->actingAs($finance)
            ->from(route('earnings.withdrawals.index'))
            ->post(route('earnings.withdrawals.paid', $withdrawal), [
                'payment_reference' => 'BANK-TRANSFER-002',
            ])
            ->assertSessionHasErrors('withdrawal');

        $this->assertSame(1, StaffWalletEntry::query()
            ->where('user_id', $staff->id)
            ->where('type', StaffWalletEntry::TYPE_WITHDRAWAL)
            ->count());
    }

    public function test_requester_cannot_record_payment_for_their_own_withdrawal(): void
    {
        $requester = $this->userWithRole('finance');
        $withdrawal = $this->withdrawal($requester, 'approved');

        $this->actingAs($requester)
            ->from(route('earnings.withdrawals.index'))
            ->post(route('earnings.withdrawals.paid', $withdrawal), [
                'payment_reference' => 'BANK-TRANSFER-003',
            ])
            ->assertSessionHasErrors('withdrawal');

        $this->assertSame('approved', $withdrawal->fresh()->status);
        $this->assertSame(0, StaffWalletEntry::query()->count());
    }

    public function test_only_requested_withdrawals_can_be_reviewed(): void
    {
        $finance = $this->userWithRole('finance');
        $staff = $this->staff();
        $withdrawal = $this->withdrawal($staff, 'approved');

        $this->actingAs($finance)
            ->from(route('earnings.withdrawals.index'))
            ->post(route('earnings.withdrawals.approve', $withdrawal), [
                'approval_reason' => 'The request has been reviewed carefully.',
            ])
            ->assertSessionHasErrors('withdrawal');

        $this->assertSame('approved', $withdrawal->fresh()->status);
    }
}
