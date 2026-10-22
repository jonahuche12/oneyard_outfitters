<?php

namespace Tests\Feature;

use App\Actions\Measurements\IssueMeasurementLink;
use App\Actions\Measurements\ResolveMeasurementLink;
use App\Actions\Measurements\RevokeMeasurementLink;
use App\Models\Contact;
use App\Models\MeasurementLink;
use App\Models\Order;
use App\Models\OrderAssignment;
use App\Models\OrderItem;
use App\Models\Organization;
use App\Models\Permission;
use App\Models\ProductionPlan;
use App\Models\Quotation;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class MeasurementLinkActionsTest extends TestCase
{
    use RefreshDatabase;

    private function staff(string $roleSlug, bool $withPermission = true, bool $active = true): User
    {
        $role = Role::firstOrCreate(['slug' => $roleSlug], ['name' => $roleSlug, 'description' => $roleSlug . '.']);
        if ($withPermission) {
            $perm = Permission::firstOrCreate(
                ['slug' => 'measurements.links.manage'],
                ['name' => 'Manage Measurement Links', 'description' => 'Test.']
            );
            $role->permissions()->syncWithoutDetaching([$perm->id]);
        }
        $user = User::factory()->create(['is_active' => $active]);
        $user->roles()->attach($role);

        return $user;
    }

    private function order(string $status = Order::STATUS_APPROVED, ?string $eligibility = 'required'): array
    {
        $organization = Organization::factory()->create();
        $contact = Contact::factory()->create([
            'organization_id' => $organization->id, 'is_active' => true, 'email' => 'c' . uniqid() . '@example.com',
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
            'expected_delivery_date' => now()->addDays(30)->toDateString(), 'status' => $status,
            'subtotal' => 30000, 'discount' => 0, 'additional_charges' => 0, 'total' => 30000,
        ]);
        $attrs = [
            'order_id' => $order->id, 'item_name' => 'Link uniform', 'quantity' => 10, 'unit' => 'piece',
            'unit_price' => 3000, 'line_total' => 30000, 'sort_order' => 0,
        ];
        if ($eligibility !== null) {
            $attrs['measurement_eligibility'] = $eligibility;
        }
        OrderItem::create($attrs);

        return [$order, $contact];
    }

    private function issue(User $actor, Order $order, Contact $contact): array
    {
        return app(IssueMeasurementLink::class)->execute($actor, $order, $contact);
    }

    public function test_admin_issues_a_link_and_only_the_hash_is_stored(): void
    {
        [$order, $contact] = $this->order();
        $admin = $this->staff('admin');

        ['link' => $link, 'token' => $token] = $this->issue($admin, $order, $contact);

        $this->assertSame(48, strlen($token));
        $this->assertSame(hash('sha256', $token), $link->token_hash);
        $this->assertDatabaseMissing('measurement_links', ['token_hash' => $token]);
        $this->assertSame(1, $link->is_active_slot);
        $this->assertTrue($link->expires_at->between(now()->addDays(3)->subMinute(), now()->addDays(3)->addMinute()));
        $this->assertTrue($link->expires_at->greaterThanOrEqualTo(now()->addDays(3)->subMinute()));
        $this->assertSame($admin->id, $link->created_by);
    }

    public function test_current_coordinator_may_issue_but_other_staff_may_not(): void
    {
        [$order, $contact] = $this->order();
        $coordinator = $this->staff('coordinator');
        OrderAssignment::create([
            'order_id' => $order->id, 'user_id' => $coordinator->id, 'assigned_by' => $coordinator->id,
            'assigned_at' => now(), 'reason' => 'Test assignment.',
        ]);
        $other = $this->staff('coordinator');

        $this->assertNotNull($this->issue($coordinator, $order, $contact)['link']);

        $this->expectException(AuthorizationException::class);
        $this->issue($other, $order, $contact);
    }

    public function test_actor_without_permission_or_inactive_is_denied(): void
    {
        [$order, $contact] = $this->order();

        foreach ([$this->staff('admin-no-perm', false), $this->staff('admin-inactive', true, false)] as $actor) {
            try {
                $this->issue($actor, $order, $contact);
                $this->fail('Expected authorization failure.');
            } catch (AuthorizationException) {
                $this->assertSame(0, MeasurementLink::count());
            }
        }
    }

    public function test_reissue_revokes_the_previous_active_link(): void
    {
        [$order, $contact] = $this->order();
        $admin = $this->staff('admin');

        $first = $this->issue($admin, $order, $contact)['link'];
        $second = $this->issue($admin, $order, $contact)['link'];

        $first->refresh();
        $this->assertNotNull($first->revoked_at);
        $this->assertNull($first->is_active_slot);
        $this->assertSame(1, $second->is_active_slot);
        $this->assertSame(1, MeasurementLink::where('is_active_slot', 1)->count());
    }

    public function test_order_state_items_and_contact_rules(): void
    {
        $admin = $this->staff('admin');

        [$inactive, $c1] = $this->order(Order::STATUS_DELIVERED);
        [$noRequired, $c2] = $this->order(Order::STATUS_APPROVED, 'not_required');
        [$pending, $c3] = $this->order(Order::STATUS_APPROVED, null);
        [$ok, $c4] = $this->order();
        $foreign = Contact::factory()->create(['is_active' => true, 'email' => 'x@example.com']);
        $noEmail = Contact::factory()->create(['organization_id' => $ok->organization_id, 'is_active' => true, 'email' => '']);
        $dormant = Contact::factory()->create(['organization_id' => $ok->organization_id, 'is_active' => false, 'email' => 'd@example.com']);

        foreach ([[$inactive, $c1], [$noRequired, $c2], [$pending, $c3], [$ok, $foreign], [$ok, $noEmail], [$ok, $dormant]] as [$o, $c]) {
            try {
                $this->issue($admin, $o, $c);
                $this->fail('Expected validation failure.');
            } catch (ValidationException) {
                $this->assertTrue(true);
            }
        }
        $this->assertSame(0, MeasurementLink::count());
    }

    public function test_cannot_issue_once_a_production_plan_exists(): void
    {
        [$order, $contact] = $this->order(Order::STATUS_IN_PRODUCTION);
        $admin = $this->staff('admin');
        ProductionPlan::create(['order_id' => $order->id, 'coordinator_id' => $admin->id, 'created_by' => $admin->id]);

        $this->expectException(ValidationException::class);
        $this->issue($admin, $order, $contact);
    }

    public function test_revoke_requires_a_reason_and_works_once(): void
    {
        [$order, $contact] = $this->order();
        $admin = $this->staff('admin');
        $link = $this->issue($admin, $order, $contact)['link'];

        try {
            app(RevokeMeasurementLink::class)->execute($admin, $link, ['reason' => 'short']);
            $this->fail('Expected validation failure.');
        } catch (ValidationException) {
            $this->assertNull($link->fresh()->revoked_at);
        }

        $revoked = app(RevokeMeasurementLink::class)->execute($admin, $link, ['reason' => 'Wrong contact was selected.']);
        $this->assertNotNull($revoked->revoked_at);
        $this->assertNull($revoked->is_active_slot);
        $this->assertSame('Wrong contact was selected.', $revoked->revoke_reason);

        $this->expectException(ValidationException::class);
        app(RevokeMeasurementLink::class)->execute($admin, $link, ['reason' => 'Revoking a second time.']);
    }

    public function test_resolve_returns_a_valid_link_and_records_access(): void
    {
        [$order, $contact] = $this->order();
        ['link' => $link, 'token' => $token] = $this->issue($this->staff('admin'), $order, $contact);

        $resolved = app(ResolveMeasurementLink::class)->execute($token);

        $this->assertSame($link->id, $resolved->id);
        $this->assertNotNull($resolved->first_accessed_at);
        $this->assertNotNull($resolved->last_accessed_at);
    }

    public function test_resolve_rejects_bad_expired_revoked_and_stale_tokens(): void
    {
        $admin = $this->staff('admin');
        $resolve = fn (?string $t) => app(ResolveMeasurementLink::class)->execute($t);

        $this->assertNull($resolve(null));
        $this->assertNull($resolve('short'));
        $this->assertNull($resolve(str_repeat('a', 48)));

        [$o1, $c1] = $this->order();
        ['link' => $expired, 'token' => $t1] = $this->issue($admin, $o1, $c1);
        $expired->forceFill(['expires_at' => now()->subMinute()])->save();
        $this->assertNull($resolve($t1));

        [$o2, $c2] = $this->order();
        ['link' => $l2, 'token' => $t2] = $this->issue($admin, $o2, $c2);
        app(RevokeMeasurementLink::class)->execute($admin, $l2, ['reason' => 'No longer needed here.']);
        $this->assertNull($resolve($t2));

        [$o3, $c3] = $this->order();
        ['token' => $t3] = $this->issue($admin, $o3, $c3);
        $o3->items()->update(['measurement_eligibility' => 'not_required']);
        $this->assertNull($resolve($t3));

        [$o4, $c4] = $this->order();
        ['token' => $t4] = $this->issue($admin, $o4, $c4);
        $o4->update(['status' => Order::STATUS_CANCELLED]);
        $this->assertNull($resolve($t4));
    }
}
