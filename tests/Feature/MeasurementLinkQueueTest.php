<?php

namespace Tests\Feature;

use App\Jobs\SendMeasurementLinkEmail;
use App\Mail\MeasurementLinkInvitation;
use App\Models\Contact;
use App\Models\MeasurementLink;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Organization;
use App\Models\Permission;
use App\Models\Quotation;
use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class MeasurementLinkQueueTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $role = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'admin', 'description' => 'Admin.']);
        foreach (['measurements.links.manage', 'orders.view'] as $slug) {
            $perm = Permission::firstOrCreate(['slug' => $slug], ['name' => $slug, 'description' => $slug . '.']);
            $role->permissions()->syncWithoutDetaching([$perm->id]);
        }
        $user = User::factory()->create(['is_active' => true]);
        $user->roles()->attach($role);

        return $user;
    }

    private function order(): array
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
            'expected_delivery_date' => now()->addDays(30)->toDateString(), 'status' => Order::STATUS_APPROVED,
            'subtotal' => 30000, 'discount' => 0, 'additional_charges' => 0, 'total' => 30000,
        ]);
        OrderItem::create([
            'order_id' => $order->id, 'item_name' => 'Queue uniform', 'quantity' => 10, 'unit' => 'piece',
            'unit_price' => 3000, 'line_total' => 30000, 'sort_order' => 0, 'measurement_eligibility' => 'required',
        ]);

        return [$order, $contact];
    }

    public function test_sending_queues_an_encrypted_job_and_does_not_send_inline(): void
    {
        Bus::fake();
        Mail::fake();
        [$order, $contact] = $this->order();

        $this->actingAs($this->admin())
            ->post(route('orders.measurement-links.store', $order), ['contact_ids' => [$contact->id]])
            ->assertSessionHas('status');

        Bus::assertDispatched(SendMeasurementLinkEmail::class, 1);
        Mail::assertNothingSent();
        $this->assertNull(MeasurementLink::firstOrFail()->sent_at);
        $this->assertInstanceOf(ShouldBeEncrypted::class, new SendMeasurementLinkEmail(MeasurementLink::firstOrFail(), 'x'));
    }

    public function test_job_sends_the_email_with_a_matching_token_and_sets_sent_at(): void
    {
        Mail::fake();
        Bus::fake();
        [$order, $contact] = $this->order();
        $this->actingAs($this->admin())
            ->post(route('orders.measurement-links.store', $order), ['contact_ids' => [$contact->id]]);

        $dispatched = null;
        Bus::assertDispatched(SendMeasurementLinkEmail::class, function ($job) use (&$dispatched) {
            $dispatched = $job;
            return true;
        });

        $dispatched->handle();

        $link = MeasurementLink::firstOrFail();
        $this->assertNotNull($link->sent_at);
        Mail::assertSent(MeasurementLinkInvitation::class, fn ($m) => $m->hasTo($contact->email)
            && hash('sha256', basename($m->measurementUrl)) === $link->token_hash);
    }

    public function test_job_skips_a_link_revoked_while_queued(): void
    {
        Mail::fake();
        [$order, $contact] = $this->order();
        $admin = $this->admin();
        ['link' => $link, 'token' => $token] = app(\App\Actions\Measurements\IssueMeasurementLink::class)
            ->execute($admin, $order, $contact);
        app(\App\Actions\Measurements\RevokeMeasurementLink::class)
            ->execute($admin, $link, ['reason' => 'Revoked before sending.']);

        (new SendMeasurementLinkEmail($link, $token))->handle();

        Mail::assertNothingSent();
        $this->assertNull($link->fresh()->sent_at);
    }

    public function test_job_failure_leaves_sent_at_null_so_it_can_retry(): void
    {
        [$order, $contact] = $this->order();
        ['link' => $link, 'token' => $token] = app(\App\Actions\Measurements\IssueMeasurementLink::class)
            ->execute($this->admin(), $order, $contact);
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('smtp down'));

        try {
            (new SendMeasurementLinkEmail($link, $token))->handle();
            $this->fail('Expected exception.');
        } catch (\RuntimeException) {
            $this->assertNull($link->fresh()->sent_at);
        }
    }
}
