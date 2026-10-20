<?php

namespace Tests\Feature;

use App\Actions\Measurements\IssueMeasurementLink;
use App\Actions\Measurements\RevokeMeasurementLink;
use App\Models\Contact;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Organization;
use App\Models\Permission;
use App\Models\Quotation;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicMeasurementPageTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $role = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'admin', 'description' => 'Admin.']);
        $perm = Permission::firstOrCreate(['slug' => 'measurements.links.manage'], ['name' => 'm', 'description' => 'm.']);
        $role->permissions()->syncWithoutDetaching([$perm->id]);
        $user = User::factory()->create(['is_active' => true]);
        $user->roles()->attach($role);

        return $user;
    }

    private function issued(): array
    {
        $organization = Organization::factory()->create(['name' => 'Greenfield Academy']);
        $contact = Contact::factory()->create([
            'organization_id' => $organization->id, 'is_active' => true, 'email' => 'p@example.com',
        ]);
        $quotation = Quotation::create([
            'quotation_number' => 'QUO-' . uniqid(), 'organization_id' => $organization->id,
            'contact_id' => $contact->id, 'created_by' => User::factory()->create()->id,
            'quotation_date' => now()->toDateString(), 'valid_until' => now()->addDays(30)->toDateString(),
            'expected_delivery_days' => 30, 'status' => Quotation::STATUS_ACCEPTED,
            'subtotal' => 30000, 'discount' => 0, 'additional_charges' => 0, 'total' => 30000,
        ]);
        $order = Order::create([
            'order_number' => 'ORD-' . uniqid(), 'organization_id' => $organization->id,
            'quotation_id' => $quotation->id, 'contact_id' => $contact->id,
            'order_date' => now()->toDateString(), 'expected_delivery_days' => 30,
            'expected_delivery_date' => now()->addDays(30)->toDateString(), 'status' => Order::STATUS_APPROVED,
            'subtotal' => 30000, 'discount' => 0, 'additional_charges' => 0, 'total' => 30000,
        ]);
        foreach ([['Required Shirt', 'required'], ['Skipped Cap', 'not_required']] as $i => [$name, $elig]) {
            OrderItem::create([
                'order_id' => $order->id, 'item_name' => $name, 'quantity' => 10, 'unit' => 'piece',
                'unit_price' => 3000, 'line_total' => 30000, 'sort_order' => $i, 'measurement_eligibility' => $elig,
            ]);
        }
        $result = app(IssueMeasurementLink::class)->execute($this->admin(), $order, $contact);

        return [$order, $result['link'], $result['token'], $this->admin()];
    }

    public function test_valid_link_shows_only_required_items_with_privacy_headers(): void
    {
        [$order, , $token] = $this->issued();

        $this->get(route('measurements.public.show', $token))
            ->assertOk()
            ->assertSee('Greenfield Academy')
            ->assertSee($order->order_number)
            ->assertSee('Required Shirt')
            ->assertDontSee('Skipped Cap')
            ->assertHeader('Referrer-Policy', 'no-referrer')
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    }

    public function test_opening_the_page_records_access(): void
    {
        [, $link, $token] = $this->issued();

        $this->get(route('measurements.public.show', $token))->assertOk();

        $this->assertNotNull($link->fresh()->first_accessed_at);
    }

    public function test_invalid_expired_and_revoked_links_look_identical(): void
    {
        [, $link, $token, $admin] = $this->issued();

        $bad = $this->get(route('measurements.public.show', str_repeat('z', 48)))
            ->assertNotFound()->assertSee('This link is not valid');
        $badBody = $bad->getContent();

        $link->forceFill(['expires_at' => now()->subMinute()])->save();
        $this->get(route('measurements.public.show', $token))->assertNotFound();
        $this->assertSame($badBody, $this->get(route('measurements.public.show', $token))->getContent());

        $link->forceFill(['expires_at' => now()->addDay()])->save();
        app(RevokeMeasurementLink::class)->execute($admin, $link, ['reason' => 'Revoked for this test.']);
        $this->assertSame($badBody, $this->get(route('measurements.public.show', $token))->getContent());
    }
}
