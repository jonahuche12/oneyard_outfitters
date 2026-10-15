<?php

namespace Tests\Feature\Earnings;

use App\Actions\Earnings\AdjustStaffWallet;
use App\Actions\Earnings\CreateProcurementCommission;
use App\Models\ProcurementOffer;
use App\Models\Role;
use App\Models\StaffWalletEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProcurementCommissionTest extends TestCase
{
    use RefreshDatabase;

    private function user(bool $active = true): User
    {
        return User::factory()->create(['is_active' => $active]);
    }

    private function procurement(string $commission = '100.00'): int
    {
        return DB::table('procurements')->insertGetId([
            'order_id' => null,
            'created_by' => $this->user()->id,
            'item_name' => 'Fabric',
            'quantity' => '50.00',
            'unit' => 'yard',
            'maximum_unit_price' => '1000.00',
            'commission_per_unit' => $commission,
            'status' => 'ready',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function offer(int $procurementId, User $user, string $status = 'accepted', ?string $delivered = '20.00'): ProcurementOffer
    {
        $id = DB::table('procurement_offers')->insertGetId([
            'procurement_id' => $procurementId,
            'user_id' => $user->id,
            'quantity' => '30.00',
            'unit_price' => '900.00',
            'total_price' => '27000.00',
            'status' => $status,
            'submitted_at' => now(),
            'delivered_quantity' => $delivered,
            'delivered_by' => $delivered ? $user->id : null,
            'delivered_at' => $delivered ? now() : null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return ProcurementOffer::query()->findOrFail($id);
    }

    private function action(): CreateProcurementCommission
    {
        return app(CreateProcurementCommission::class);
    }

    public function test_commission_is_delivered_quantity_times_rate(): void
    {
        $staff = $this->user();
        $offer = $this->offer($this->procurement('100.00'), $staff);

        $entry = $this->action()->execute($offer);

        $this->assertSame(StaffWalletEntry::TYPE_COMMISSION, $entry->type);
        $this->assertSame('2000.00', $entry->amount);
        $this->assertSame($offer->id, $entry->procurement_offer_id);
        $this->assertStringContainsString('20', $entry->reason);
        $this->assertStringContainsString('Fabric', $entry->reason);
        $this->assertSame('2000.00', StaffWalletEntry::balanceFor($staff));
    }

    public function test_quantity_basis_is_delivered_not_offered(): void
    {
        $staff = $this->user();
        $offer = $this->offer($this->procurement('100.00'), $staff, 'accepted', '12.00');

        $this->assertSame('1200.00', $this->action()->execute($offer)->amount);
    }

    public function test_credit_is_idempotent(): void
    {
        $staff = $this->user();
        $offer = $this->offer($this->procurement(), $staff);

        $first = $this->action()->execute($offer);
        $second = $this->action()->execute($offer);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, StaffWalletEntry::query()->count());
    }

    public function test_later_rate_change_does_not_alter_the_credit(): void
    {
        $staff = $this->user();
        $procurementId = $this->procurement('100.00');
        $offer = $this->offer($procurementId, $staff);
        $this->action()->execute($offer);

        DB::table('procurements')->where('id', $procurementId)->update(['commission_per_unit' => '500.00']);
        $this->action()->execute($offer);

        $this->assertSame('2000.00', StaffWalletEntry::balanceFor($staff));
    }

    public function test_zero_commission_and_undelivered_offers_credit_nothing(): void
    {
        $staff = $this->user();

        $this->assertNull($this->action()->execute($this->offer($this->procurement('0.00'), $staff)));
        $this->assertNull($this->action()->execute($this->offer($this->procurement(), $staff, 'accepted', null)));
        $this->assertNull($this->action()->execute($this->offer($this->procurement(), $staff, 'withdrawn')));
        $this->assertSame(0, StaffWalletEntry::query()->count());
    }

    public function test_amount_is_rounded_half_up_to_cents(): void
    {
        $staff = $this->user();
        $offer = $this->offer($this->procurement('33.33'), $staff, 'accepted', '2.50');

        $this->assertSame('83.33', $this->action()->execute($offer)->amount);
    }

    public function test_inactive_staff_are_credited(): void
    {
        $staff = $this->user(false);
        $offer = $this->offer($this->procurement(), $staff);

        $this->assertSame('2000.00', $this->action()->execute($offer)->amount);
    }

    public function test_commission_entry_can_be_reversed_once(): void
    {
        $staff = $this->user();
        $entry = $this->action()->execute($this->offer($this->procurement(), $staff));

        $actor = $this->user();
        $role = Role::factory()->create(['name' => 'Comm Role', 'slug' => 'comm-role']);
        $role->permissions()->create(['name' => AdjustStaffWallet::PERMISSION, 'slug' => AdjustStaffWallet::PERMISSION]);
        $actor->roles()->attach($role);

        app(AdjustStaffWallet::class)->reverse($entry, 'Delivery was rejected later.', $actor);

        $this->assertSame('0.00', StaffWalletEntry::balanceFor($staff));
        $this->assertSame(2, StaffWalletEntry::query()->count());
    }

    public function test_backfill_command_supports_dry_run_and_is_idempotent(): void
    {
        $staff = $this->user();
        $this->offer($this->procurement('100.00'), $staff);
        $this->offer($this->procurement('0.00'), $staff);

        $this->artisan('earnings:backfill-commissions', ['--dry-run' => true])->assertExitCode(0);
        $this->assertSame(0, StaffWalletEntry::query()->count());

        $this->artisan('earnings:backfill-commissions')->assertExitCode(0);
        $this->assertSame(1, StaffWalletEntry::query()->count());
        $this->assertSame('2000.00', StaffWalletEntry::balanceFor($staff));

        $this->artisan('earnings:backfill-commissions')->assertExitCode(0);
        $this->assertSame(1, StaffWalletEntry::query()->count());
    }
}
