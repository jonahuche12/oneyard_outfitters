<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Procurement;
use App\Models\ProcurementOffer;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProcurementIndexCardsTest extends TestCase
{
    use RefreshDatabase;

    private function viewer(): User
    {
        $role = Role::create([
            'name' => 'Procurement Viewer',
            'slug' => 'procurement-viewer',
            'description' => 'Test role.',
        ]);

        $role->permissions()->attach(
            Permission::firstOrCreate(
                ['slug' => 'procurement.view'],
                ['name' => 'Procurement View', 'description' => 'Test permission.']
            )
        );

        $user = User::factory()->create(['is_active' => true]);
        $user->roles()->attach($role);

        return $user;
    }

    private function procurement(string $name = 'School Uniform Fabric'): Procurement
    {
        return Procurement::create([
            'created_by' => User::factory()->create()->id,
            'item_name' => $name,
            'quantity' => 100,
            'unit' => 'Yard',
            'maximum_unit_price' => 3500,
            'commission_per_unit' => 100,
            'required_by' => now()->addDays(14)->toDateString(),
            'offer_deadline' => now()->addDays(7),
            'priority' => 'normal',
            'status' => Procurement::STATUS_READY,
        ]);
    }

    public function test_index_renders_each_procurement_as_a_card(): void
    {
        $procurement = $this->procurement('Navy Blue Fabric');

        $response = $this->actingAs($this->viewer())
            ->get(route('procurements.index'))
            ->assertOk()
            ->assertSee('Navy Blue Fabric')
            ->assertSee('Independent')
            ->assertSee('Submit Offer')
            ->assertSee('data-procurement-card="' . $procurement->id . '"', false);

        $this->assertStringNotContainsString('<table', $response->getContent());
    }

    public function test_card_shows_offer_and_accepted_counts(): void
    {
        $procurement = $this->procurement();

        foreach ([ProcurementOffer::STATUS_ACCEPTED, ProcurementOffer::STATUS_ACCEPTED, ProcurementOffer::STATUS_SUBMITTED] as $status) {
            ProcurementOffer::create([
                'procurement_id' => $procurement->id,
                'user_id' => User::factory()->create()->id,
                'quantity' => 10,
                'unit_price' => 3000,
                'total_price' => 30000,
                'status' => $status,
                'submitted_at' => now(),
            ]);
        }

        $this->actingAs($this->viewer())
            ->get(route('procurements.index'))
            ->assertOk()
            ->assertSee('2 accepted');
    }

    public function test_card_without_photo_shows_placeholder(): void
    {
        $this->procurement();

        $this->actingAs($this->viewer())
            ->get(route('procurements.index'))
            ->assertOk()
            ->assertSee('No reference photo');
    }

    public function test_index_paginates_twelve_cards_per_page(): void
    {
        for ($i = 1; $i <= 13; $i++) {
            $this->procurement('Item ' . $i);
        }

        $viewer = $this->viewer();

        $first = $this->actingAs($viewer)->get(route('procurements.index'))->assertOk();
        $this->assertSame(12, substr_count($first->getContent(), 'data-procurement-card='));

        $second = $this->actingAs($viewer)->get(route('procurements.index', ['page' => 2]))->assertOk();
        $this->assertSame(1, substr_count($second->getContent(), 'data-procurement-card='));
    }
}
