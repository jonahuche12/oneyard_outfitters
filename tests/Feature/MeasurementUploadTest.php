<?php

namespace Tests\Feature;

use App\Actions\Measurements\IssueMeasurementLink;
use App\Actions\Measurements\SaveMeasurementDraft;
use App\Models\Contact;
use App\Models\MeasurementSubmission;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Organization;
use App\Models\Permission;
use App\Models\Quotation;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class MeasurementUploadTest extends TestCase
{
    use RefreshDatabase;

    private Order $order;
    private OrderItem $shirt;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'admin', 'description' => 'Admin.']);
        $perm = Permission::firstOrCreate(['slug' => 'measurements.links.manage'], ['name' => 'm', 'description' => 'm.']);
        $role->permissions()->syncWithoutDetaching([$perm->id]);
        $admin = User::factory()->create(['is_active' => true]);
        $admin->roles()->attach($role);

        $organization = Organization::factory()->create();
        $contact = Contact::factory()->create(['organization_id' => $organization->id, 'is_active' => true, 'email' => 's@example.com']);
        $quotation = Quotation::create([
            'quotation_number' => 'QUO-' . uniqid(), 'organization_id' => $organization->id,
            'contact_id' => $contact->id, 'created_by' => $admin->id,
            'quotation_date' => now()->toDateString(), 'valid_until' => now()->addDays(30)->toDateString(),
            'expected_delivery_days' => 30, 'status' => Quotation::STATUS_ACCEPTED,
            'subtotal' => 1000, 'discount' => 0, 'additional_charges' => 0, 'total' => 1000,
        ]);
        $this->order = Order::create([
            'order_number' => 'ORD-' . uniqid(), 'organization_id' => $organization->id,
            'quotation_id' => $quotation->id, 'contact_id' => $contact->id,
            'order_date' => now()->toDateString(), 'expected_delivery_days' => 30,
            'expected_delivery_date' => now()->addDays(30)->toDateString(), 'status' => Order::STATUS_APPROVED,
            'subtotal' => 1000, 'discount' => 0, 'additional_charges' => 0, 'total' => 1000,
        ]);
        $this->shirt = OrderItem::create([
            'order_id' => $this->order->id, 'unit_price' => 100, 'line_total' => 100,
            'measurement_eligibility' => 'required', 'item_name' => 'Shirt', 'quantity' => 5,
            'unit' => 'pair', 'product_type' => 'shirt', 'sort_order' => 0,
        ]);

        $issued = app(IssueMeasurementLink::class)->execute($admin, $this->order, $contact);
        $this->token = $issued['token'];
    }

    private function csvFile(array $rows, string $name = 'm.csv'): UploadedFile
    {
        $out = '';
        foreach ($rows as $r) {
            $out .= implode(',', $r) . "\n";
        }

        return UploadedFile::fake()->createWithContent($name, $out);
    }

    private const HEADER = ['Name', 'Neck', 'Chest', 'Waist', 'Shoulder width', 'Sleeve length', 'Shirt length'];

    private function url(?int $item = null, ?string $token = null): string
    {
        return route('measurements.public.upload', ['token' => $token ?? $this->token, 'item' => $item ?? $this->shirt->id]);
    }

    private function saveExisting(): void
    {
        app(SaveMeasurementDraft::class)->saveEntry($this->token, $this->shirt->id, null, [
            'label' => 'Old', 'measurements' => ['neck' => '15', 'chest' => '38', 'waist' => '32', 'shoulder' => '17', 'sleeve' => '24', 'length' => '29'],
        ]);
    }

    public function test_valid_file_replaces_draft_entries(): void
    {
        $this->saveExisting();
        $file = $this->csvFile([self::HEADER, ['Ada', 15, 38, 32, 17, 24, 29], ['Bola', 16, 40, 34, 18, 25, 30]]);

        $this->postJson($this->url(), ['file' => $file])->assertOk()->assertJson(['ok' => true, 'saved_count' => 2]);

        $s = MeasurementSubmission::where('order_item_id', $this->shirt->id)->firstOrFail();
        $this->assertSame('draft', $s->status);
        $this->assertSame('excel', $s->input_source);
        $this->assertSame(['Ada', 'Bola'], $s->individualMeasurements->pluck('person_label')->all());
    }

    public function test_invalid_file_leaves_the_existing_draft_untouched(): void
    {
        $this->saveExisting();
        $file = $this->csvFile([self::HEADER, ['Ada', 15, 38, 'abc', 17, 24, 29]]);

        $this->postJson($this->url(), ['file' => $file])->assertStatus(422);

        $s = MeasurementSubmission::where('order_item_id', $this->shirt->id)->firstOrFail();
        $this->assertSame(['Old'], $s->individualMeasurements->pluck('person_label')->all());
        $this->assertSame('manual', $s->input_source);
    }

    public function test_invalid_token_is_rejected_and_saves_nothing(): void
    {
        $file = $this->csvFile([self::HEADER, ['Ada', 15, 38, 32, 17, 24, 29]]);

        $this->postJson($this->url(null, 'bogus-token'), ['file' => $file])->assertStatus(422);

        $this->assertSame(0, MeasurementSubmission::count());
    }

    public function test_missing_file_is_rejected(): void
    {
        $this->postJson($this->url(), [])->assertStatus(422);
    }

    public function test_submitted_item_cannot_be_replaced(): void
    {
        $this->saveExisting();
        MeasurementSubmission::where('order_item_id', $this->shirt->id)->update(['status' => 'submitted']);
        $file = $this->csvFile([self::HEADER, ['Ada', 15, 38, 32, 17, 24, 29]]);

        $this->postJson($this->url(), ['file' => $file])->assertStatus(422);

        $this->assertSame('Old', MeasurementSubmission::where('order_item_id', $this->shirt->id)->first()->individualMeasurements->first()->person_label);
    }

    public function test_upload_is_throttled(): void
    {
        $file = fn () => $this->csvFile([self::HEADER]);

        for ($i = 0; $i < 5; $i++) {
            $this->postJson($this->url(), ['file' => $file()]);
        }

        $this->postJson($this->url(), ['file' => $file()])->assertStatus(429);
    }
}
