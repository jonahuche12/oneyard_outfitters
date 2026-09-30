<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Procurement;
use App\Models\ProcurementOffer;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class ProcurementOfferTest extends TestCase
{
    use RefreshDatabase;

    private function permission(string $slug): Permission
    {
        return Permission::firstOrCreate(
            ['slug' => $slug],
            [
                'name' => ucwords(str_replace(['.', '-'], ' ', $slug)),
                'description' => 'Test permission.',
            ]
        );
    }

    private function userWithRole(
        string $roleSlug,
        array $permissions = []
    ): User {
        $role = Role::create([
            'name' => ucwords(str_replace('-', ' ', $roleSlug)),
            'slug' => $roleSlug,
            'description' => 'Test role.',
        ]);

        foreach ($permissions as $permission) {
            $role->permissions()->attach(
                $this->permission($permission)
            );
        }

        $user = User::factory()->create([
            'is_active' => true,
        ]);

        $user->roles()->attach($role);

        return $user;
    }

    private function procurement(array $overrides = []): Procurement
    {
        $user = User::factory()->create();

        return Procurement::create(array_merge([
            'created_by' => $user->id,
            'item_name' => 'School Uniform Fabric',
            'description' => 'Navy blue uniform fabric.',
            'quantity' => 100,
            'unit' => 'Yard',
            'maximum_unit_price' => 3500,
            'commission_per_unit' => 100,
            'required_by' => now()->addDays(14)->toDateString(),
            'offer_deadline' => now()->addDays(7),
            'priority' => 'normal',
            'status' => Procurement::STATUS_READY,
        ], $overrides));
    }

    private function offerPayload(array $overrides = []): array
    {
        return array_merge([
            'quantity' => 80,
            'unit_price' => 3200,
            'notes' => 'We can supply the required fabric.',
        ], $overrides);
    }

    public function test_staff_with_procurement_view_permission_can_submit_offer(): void
    {
        $user = $this->userWithRole(
            'procurement-viewer',
            ['procurement.view']
        );

        $procurement = $this->procurement();

        $this->assertTrue(
            Gate::forUser($user)->allows('submitOffer', $procurement)
        );

        $response = $this
            ->actingAs($user)
            ->get(route('procurements.offers.create', $procurement));

        $response->assertOk()
            ->assertViewIs('procurements.offers.create')
            ->assertSee('Submit Supplier Offer')
            ->assertSee($procurement->item_name);
    }

    public function test_admin_can_submit_offer_without_procurement_view_permission(): void
    {
        $admin = $this->userWithRole('admin');

        $procurement = $this->procurement();

        $this->assertTrue(
            Gate::forUser($admin)->allows('submitOffer', $procurement)
        );

        $response = $this
            ->actingAs($admin)
            ->post(
                route('procurements.offers.store', $procurement),
                $this->offerPayload()
            );

        $response->assertRedirect(
            route('procurements.show', $procurement)
        );

        $this->assertDatabaseHas('procurement_offers', [
            'procurement_id' => $procurement->id,
            'user_id' => $admin->id,
            'quantity' => 80,
            'unit_price' => 3200,
            'total_price' => 256000,
            'notes' => 'We can supply the required fabric.',
            'status' => ProcurementOffer::STATUS_SUBMITTED,
        ]);
    }

    public function test_super_admin_can_submit_offer_without_procurement_view_permission(): void
    {
        $superAdmin = $this->userWithRole('super-admin');

        $procurement = $this->procurement();

        $this->assertTrue(
            Gate::forUser($superAdmin)->allows('submitOffer', $procurement)
        );

        $response = $this
            ->actingAs($superAdmin)
            ->post(
                route('procurements.offers.store', $procurement),
                $this->offerPayload()
            );

        $response->assertRedirect(
            route('procurements.show', $procurement)
        );

        $this->assertDatabaseHas('procurement_offers', [
            'procurement_id' => $procurement->id,
            'user_id' => $superAdmin->id,
            'status' => ProcurementOffer::STATUS_SUBMITTED,
        ]);
    }

    public function test_user_without_procurement_view_permission_cannot_submit_offer(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
        ]);

        $procurement = $this->procurement();

        $this->assertFalse(
            Gate::forUser($user)->allows('submitOffer', $procurement)
        );

        $response = $this
            ->actingAs($user)
            ->post(
                route('procurements.offers.store', $procurement),
                $this->offerPayload()
            );

        $response->assertForbidden();

        $this->assertDatabaseCount('procurement_offers', 0);
    }

    public function test_guest_cannot_submit_offer(): void
    {
        $procurement = $this->procurement();

        $response = $this->post(
            route('procurements.offers.store', $procurement),
            $this->offerPayload()
        );

        $response->assertRedirect(route('login'));

        $this->assertDatabaseCount('procurement_offers', 0);
    }

    public function test_offer_cannot_be_submitted_for_draft_procurement(): void
    {
        $user = $this->userWithRole(
            'procurement-viewer',
            ['procurement.view']
        );

        $procurement = $this->procurement([
            'status' => Procurement::STATUS_DRAFT,
        ]);

        $response = $this
            ->actingAs($user)
            ->post(
                route('procurements.offers.store', $procurement),
                $this->offerPayload()
            );

        $response->assertSessionHasErrors('procurement');

        $this->assertDatabaseCount('procurement_offers', 0);
    }

    public function test_offer_cannot_be_submitted_for_in_progress_procurement(): void
    {
        $user = $this->userWithRole(
            'procurement-viewer',
            ['procurement.view']
        );

        $procurement = $this->procurement([
            'status' => Procurement::STATUS_IN_PROGRESS,
        ]);

        $response = $this
            ->actingAs($user)
            ->post(
                route('procurements.offers.store', $procurement),
                $this->offerPayload()
            );

        $response->assertSessionHasErrors('procurement');

        $this->assertDatabaseCount('procurement_offers', 0);
    }

    public function test_offer_cannot_be_submitted_for_fulfilled_procurement(): void
    {
        $user = $this->userWithRole(
            'procurement-viewer',
            ['procurement.view']
        );

        $procurement = $this->procurement([
            'status' => Procurement::STATUS_FULFILLED,
        ]);

        $response = $this
            ->actingAs($user)
            ->post(
                route('procurements.offers.store', $procurement),
                $this->offerPayload()
            );

        $response->assertSessionHasErrors('procurement');

        $this->assertDatabaseCount('procurement_offers', 0);
    }

    public function test_offer_cannot_be_submitted_for_cancelled_procurement(): void
    {
        $user = $this->userWithRole(
            'procurement-viewer',
            ['procurement.view']
        );

        $procurement = $this->procurement([
            'status' => Procurement::STATUS_CANCELLED,
        ]);

        $response = $this
            ->actingAs($user)
            ->post(
                route('procurements.offers.store', $procurement),
                $this->offerPayload()
            );

        $response->assertSessionHasErrors('procurement');

        $this->assertDatabaseCount('procurement_offers', 0);
    }

    public function test_offer_cannot_be_submitted_after_deadline(): void
    {
        $user = $this->userWithRole(
            'procurement-viewer',
            ['procurement.view']
        );

        $procurement = $this->procurement([
            'offer_deadline' => now()->subMinute(),
        ]);

        $response = $this
            ->actingAs($user)
            ->post(
                route('procurements.offers.store', $procurement),
                $this->offerPayload()
            );

        $response->assertSessionHasErrors('procurement');

        $this->assertDatabaseCount('procurement_offers', 0);
    }

    public function test_ready_procurement_without_deadline_accepts_offer(): void
    {
        $user = $this->userWithRole(
            'procurement-viewer',
            ['procurement.view']
        );

        $procurement = $this->procurement([
            'offer_deadline' => null,
        ]);

        $response = $this
            ->actingAs($user)
            ->post(
                route('procurements.offers.store', $procurement),
                $this->offerPayload()
            );

        $response->assertRedirect(
            route('procurements.show', $procurement)
        );

        $this->assertDatabaseHas('procurement_offers', [
            'procurement_id' => $procurement->id,
            'user_id' => $user->id,
            'status' => ProcurementOffer::STATUS_SUBMITTED,
        ]);
    }

    public function test_offer_quantity_must_be_greater_than_zero(): void
    {
        $user = $this->userWithRole(
            'procurement-viewer',
            ['procurement.view']
        );

        $procurement = $this->procurement();

        $response = $this
            ->actingAs($user)
            ->post(
                route('procurements.offers.store', $procurement),
                $this->offerPayload([
                    'quantity' => 0,
                ])
            );

        $response->assertSessionHasErrors('quantity');

        $this->assertDatabaseCount('procurement_offers', 0);
    }

    public function test_offer_quantity_cannot_exceed_procurement_quantity(): void
    {
        $user = $this->userWithRole(
            'procurement-viewer',
            ['procurement.view']
        );

        $procurement = $this->procurement([
            'quantity' => 100,
        ]);

        $response = $this
            ->actingAs($user)
            ->post(
                route('procurements.offers.store', $procurement),
                $this->offerPayload([
                    'quantity' => 100.01,
                ])
            );

        $response->assertSessionHasErrors('quantity');

        $this->assertDatabaseCount('procurement_offers', 0);
    }

    public function test_offer_unit_price_cannot_exceed_maximum_unit_price(): void
    {
        $user = $this->userWithRole(
            'procurement-viewer',
            ['procurement.view']
        );

        $procurement = $this->procurement([
            'maximum_unit_price' => 3500,
        ]);

        $response = $this
            ->actingAs($user)
            ->post(
                route('procurements.offers.store', $procurement),
                $this->offerPayload([
                    'unit_price' => 3500.01,
                ])
            );

        $response->assertSessionHasErrors('unit_price');

        $this->assertDatabaseCount('procurement_offers', 0);
    }

    public function test_zero_unit_price_is_allowed(): void
    {
        $user = $this->userWithRole(
            'procurement-viewer',
            ['procurement.view']
        );

        $procurement = $this->procurement();

        $response = $this
            ->actingAs($user)
            ->post(
                route('procurements.offers.store', $procurement),
                $this->offerPayload([
                    'unit_price' => 0,
                ])
            );

        $response->assertRedirect(
            route('procurements.show', $procurement)
        );

        $this->assertDatabaseHas('procurement_offers', [
            'procurement_id' => $procurement->id,
            'user_id' => $user->id,
            'unit_price' => 0,
            'total_price' => 0,
        ]);
    }

    public function test_total_price_is_calculated_server_side(): void
    {
        $user = $this->userWithRole(
            'procurement-viewer',
            ['procurement.view']
        );

        $procurement = $this->procurement();

        $response = $this
            ->actingAs($user)
            ->post(
                route('procurements.offers.store', $procurement),
                $this->offerPayload([
                    'quantity' => 25,
                    'unit_price' => 3000,
                    'total_price' => 1,
                ])
            );

        $response->assertRedirect(
            route('procurements.show', $procurement)
        );

        $this->assertDatabaseHas('procurement_offers', [
            'procurement_id' => $procurement->id,
            'user_id' => $user->id,
            'quantity' => 25,
            'unit_price' => 3000,
            'total_price' => 75000,
        ]);

        $this->assertDatabaseMissing('procurement_offers', [
            'procurement_id' => $procurement->id,
            'total_price' => 1,
        ]);
    }

    public function test_offer_assigns_submitting_user_status_and_timestamp_server_side(): void
    {
        $user = $this->userWithRole(
            'procurement-viewer',
            ['procurement.view']
        );

        $procurement = $this->procurement();

        $response = $this
            ->actingAs($user)
            ->post(
                route('procurements.offers.store', $procurement),
                $this->offerPayload([
                    'user_id' => User::factory()->create()->id,
                    'status' => ProcurementOffer::STATUS_ACCEPTED,
                    'submitted_at' => '2000-01-01 00:00:00',
                ])
            );

        $response->assertRedirect(
            route('procurements.show', $procurement)
        );

        $offer = ProcurementOffer::query()->first();

        expect($offer)->not->toBeNull()
            ->and($offer->user_id)->toBe($user->id)
            ->and($offer->status)->toBe(ProcurementOffer::STATUS_SUBMITTED)
            ->and($offer->submitted_at)->not->toBeNull();

        $this->assertNotSame(
            '2000-01-01 00:00:00',
            $offer->submitted_at->format('Y-m-d H:i:s')
        );
    }

    public function test_offer_notes_are_stored(): void
    {
        $user = $this->userWithRole(
            'procurement-viewer',
            ['procurement.view']
        );

        $procurement = $this->procurement();

        $response = $this
            ->actingAs($user)
            ->post(
                route('procurements.offers.store', $procurement),
                $this->offerPayload([
                    'notes' => 'Supplier can deliver within five working days.',
                ])
            );

        $response->assertRedirect(
            route('procurements.show', $procurement)
        );

        $this->assertDatabaseHas('procurement_offers', [
            'procurement_id' => $procurement->id,
            'user_id' => $user->id,
            'notes' => 'Supplier can deliver within five working days.',
        ]);
    }

    public function test_staff_can_view_their_own_offer(): void
    {
        $user = $this->userWithRole(
            'procurement-viewer',
            ['procurement.view']
        );

        $procurement = $this->procurement();

        $offer = ProcurementOffer::create([
            'procurement_id' => $procurement->id,
            'user_id' => $user->id,
            'quantity' => 80,
            'unit_price' => 3200,
            'total_price' => 256000,
            'notes' => 'My offer.',
            'status' => ProcurementOffer::STATUS_SUBMITTED,
            'submitted_at' => now(),
        ]);

        $this->assertTrue(
            Gate::forUser($user)->allows('view', $offer)
        );
    }

    public function test_staff_cannot_view_another_staff_members_offer(): void
    {
        $user = $this->userWithRole(
            'procurement-viewer',
            ['procurement.view']
        );

        $otherUser = $this->userWithRole(
            'another-procurement-viewer',
            ['procurement.view']
        );

        $procurement = $this->procurement();

        $offer = ProcurementOffer::create([
            'procurement_id' => $procurement->id,
            'user_id' => $otherUser->id,
            'quantity' => 80,
            'unit_price' => 3200,
            'total_price' => 256000,
            'status' => ProcurementOffer::STATUS_SUBMITTED,
            'submitted_at' => now(),
        ]);

        $this->assertFalse(
            Gate::forUser($user)->allows('view', $offer)
        );
    }

    public function test_admin_can_view_another_staff_members_offer(): void
    {
        $admin = $this->userWithRole('admin');

        $staff = $this->userWithRole(
            'procurement-viewer',
            ['procurement.view']
        );

        $procurement = $this->procurement();

        $offer = ProcurementOffer::create([
            'procurement_id' => $procurement->id,
            'user_id' => $staff->id,
            'quantity' => 80,
            'unit_price' => 3200,
            'total_price' => 256000,
            'status' => ProcurementOffer::STATUS_SUBMITTED,
            'submitted_at' => now(),
        ]);

        $this->assertTrue(
            Gate::forUser($admin)->allows('view', $offer)
        );
    }

    public function test_staff_can_update_their_own_submitted_offer(): void
    {
        $user = $this->userWithRole(
            'procurement-viewer',
            ['procurement.view']
        );

        $procurement = $this->procurement();

        $offer = ProcurementOffer::create([
            'procurement_id' => $procurement->id,
            'user_id' => $user->id,
            'quantity' => 80,
            'unit_price' => 3200,
            'total_price' => 256000,
            'notes' => 'Original offer.',
            'status' => ProcurementOffer::STATUS_SUBMITTED,
            'submitted_at' => now(),
        ]);

        $this->assertTrue(
            Gate::forUser($user)->allows('update', $offer)
        );

        $response = $this
            ->actingAs($user)
            ->put(
                route('procurements.offers.update', $offer),
                [
                    'quantity' => 60,
                    'unit_price' => 3000,
                    'notes' => 'Updated offer.',
                ]
            );

        $response->assertRedirect(
            route('procurements.offers.show', $offer)
        );

        $this->assertDatabaseHas('procurement_offers', [
            'id' => $offer->id,
            'quantity' => 60,
            'unit_price' => 3000,
            'total_price' => 180000,
            'notes' => 'Updated offer.',
            'status' => ProcurementOffer::STATUS_SUBMITTED,
        ]);
    }

    public function test_staff_cannot_update_another_staff_members_offer(): void
    {
        $user = $this->userWithRole(
            'procurement-viewer',
            ['procurement.view']
        );

        $otherUser = $this->userWithRole(
            'another-procurement-viewer',
            ['procurement.view']
        );

        $procurement = $this->procurement();

        $offer = ProcurementOffer::create([
            'procurement_id' => $procurement->id,
            'user_id' => $otherUser->id,
            'quantity' => 80,
            'unit_price' => 3200,
            'total_price' => 256000,
            'status' => ProcurementOffer::STATUS_SUBMITTED,
            'submitted_at' => now(),
        ]);

        $this->assertFalse(
            Gate::forUser($user)->allows('update', $offer)
        );

        $response = $this
            ->actingAs($user)
            ->put(
                route('procurements.offers.update', $offer),
                [
                    'quantity' => 60,
                    'unit_price' => 3000,
                    'notes' => 'Unauthorized update.',
                ]
            );

        $response->assertForbidden();

        $this->assertDatabaseHas('procurement_offers', [
            'id' => $offer->id,
            'quantity' => 80,
            'unit_price' => 3200,
            'total_price' => 256000,
        ]);
    }

    public function test_staff_can_withdraw_their_own_submitted_offer(): void
    {
        $user = $this->userWithRole(
            'procurement-viewer',
            ['procurement.view']
        );

        $procurement = $this->procurement();

        $offer = ProcurementOffer::create([
            'procurement_id' => $procurement->id,
            'user_id' => $user->id,
            'quantity' => 80,
            'unit_price' => 3200,
            'total_price' => 256000,
            'status' => ProcurementOffer::STATUS_SUBMITTED,
            'submitted_at' => now(),
        ]);

        $this->assertTrue(
            Gate::forUser($user)->allows('withdraw', $offer)
        );

        $response = $this
            ->actingAs($user)
            ->post(
                route('procurements.offers.withdraw', $offer)
            );

        $response->assertRedirect(
            route('procurements.show', $procurement)
        );

        $this->assertDatabaseHas('procurement_offers', [
            'id' => $offer->id,
            'status' => ProcurementOffer::STATUS_WITHDRAWN,
        ]);
    }

    public function test_withdrawn_offer_cannot_be_updated(): void
    {
        $user = $this->userWithRole(
            'procurement-viewer',
            ['procurement.view']
        );

        $procurement = $this->procurement();

        $offer = ProcurementOffer::create([
            'procurement_id' => $procurement->id,
            'user_id' => $user->id,
            'quantity' => 80,
            'unit_price' => 3200,
            'total_price' => 256000,
            'status' => ProcurementOffer::STATUS_WITHDRAWN,
            'submitted_at' => now(),
        ]);

        $this->assertFalse(
            Gate::forUser($user)->allows('update', $offer)
        );

        $response = $this
            ->actingAs($user)
            ->put(
                route('procurements.offers.update', $offer),
                [
                    'quantity' => 60,
                    'unit_price' => 3000,
                ]
            );

        $response->assertForbidden();

        $this->assertDatabaseHas('procurement_offers', [
            'id' => $offer->id,
            'status' => ProcurementOffer::STATUS_WITHDRAWN,
            'quantity' => 80,
            'unit_price' => 3200,
        ]);
    }

    public function test_staff_can_submit_a_maximum_of_three_offers_for_one_procurement(): void
    {
        $user = $this->userWithRole(
            'procurement-viewer',
            ['procurement.view']
        );

        $procurement = $this->procurement();

        for ($i = 1; $i <= 3; $i++) {
            $response = $this
                ->actingAs($user)
                ->post(
                    route('procurements.offers.store', $procurement),
                    $this->offerPayload([
                        'quantity' => 80 - $i,
                        'unit_price' => 3200 - ($i * 100),
                    ])
                );

            $response->assertRedirect(
                route('procurements.show', $procurement)
            );
        }

        $this->assertSame(
            3,
            ProcurementOffer::query()
                ->where('procurement_id', $procurement->id)
                ->where('user_id', $user->id)
                ->count()
        );
    }

    public function test_fourth_offer_for_same_procurement_is_rejected(): void
    {
        $user = $this->userWithRole(
            'procurement-viewer',
            ['procurement.view']
        );

        $procurement = $this->procurement();

        for ($i = 0; $i < 3; $i++) {
            ProcurementOffer::create([
                'procurement_id' => $procurement->id,
                'user_id' => $user->id,
                'quantity' => 80,
                'unit_price' => 3200,
                'total_price' => 256000,
                'status' => ProcurementOffer::STATUS_SUBMITTED,
                'submitted_at' => now(),
            ]);
        }

        $response = $this
            ->actingAs($user)
            ->post(
                route('procurements.offers.store', $procurement),
                $this->offerPayload()
            );

        $response->assertSessionHasErrors('offer');

        $this->assertSame(
            3,
            ProcurementOffer::query()
                ->where('procurement_id', $procurement->id)
                ->where('user_id', $user->id)
                ->count()
        );
    }

    public function test_three_offer_limit_is_per_staff_member(): void
    {
        $firstUser = $this->userWithRole(
            'first-procurement-viewer',
            ['procurement.view']
        );

        $secondUser = $this->userWithRole(
            'second-procurement-viewer',
            ['procurement.view']
        );

        $procurement = $this->procurement();

        for ($i = 0; $i < 3; $i++) {
            ProcurementOffer::create([
                'procurement_id' => $procurement->id,
                'user_id' => $firstUser->id,
                'quantity' => 80,
                'unit_price' => 3200,
                'total_price' => 256000,
                'status' => ProcurementOffer::STATUS_SUBMITTED,
                'submitted_at' => now(),
            ]);
        }

        $response = $this
            ->actingAs($secondUser)
            ->post(
                route('procurements.offers.store', $procurement),
                $this->offerPayload()
            );

        $response->assertRedirect(
            route('procurements.show', $procurement)
        );

        $this->assertSame(
            3,
            ProcurementOffer::query()
                ->where('procurement_id', $procurement->id)
                ->where('user_id', $firstUser->id)
                ->count()
        );

        $this->assertSame(
            1,
            ProcurementOffer::query()
                ->where('procurement_id', $procurement->id)
                ->where('user_id', $secondUser->id)
                ->count()
        );
    }

    public function test_successful_offer_submission_creates_exactly_one_offer(): void
    {
        $user = $this->userWithRole(
            'procurement-viewer',
            ['procurement.view']
        );

        $procurement = $this->procurement();

        $response = $this
            ->actingAs($user)
            ->post(
                route('procurements.offers.store', $procurement),
                $this->offerPayload()
            );

        $response->assertRedirect(
            route('procurements.show', $procurement)
        );

        expect(ProcurementOffer::query()->count())->toBe(1);

        $offer = ProcurementOffer::query()->first();

        expect($offer->procurement_id)->toBe($procurement->id)
            ->and($offer->user_id)->toBe($user->id)
            ->and($offer->quantity)->toBe('80.00')
            ->and($offer->unit_price)->toBe('3200.00')
            ->and($offer->total_price)->toBe('256000.00')
            ->and($offer->status)
            ->toBe(ProcurementOffer::STATUS_SUBMITTED);
    }
}
