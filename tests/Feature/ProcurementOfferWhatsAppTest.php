<?php

namespace Tests\Feature;

use App\Actions\WhatsApp\SendWhatsAppTemplate;
use App\Jobs\SendProcurementOfferAcceptedWhatsApp;
use App\Mail\ProcurementOfferAccepted;
use App\Models\Permission;
use App\Models\Procurement;
use App\Models\ProcurementOffer;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ProcurementOfferWhatsAppTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $role = Role::create([
            'name' => 'Admin',
            'slug' => 'admin',
            'description' => 'Test role.',
        ]);

        $role->permissions()->attach(
            Permission::firstOrCreate(
                ['slug' => 'procurement.manage'],
                ['name' => 'Procurement Manage', 'description' => 'Test permission.']
            )
        );

        $user = User::factory()->create(['is_active' => true]);
        $user->roles()->attach($role);

        return $user;
    }

    private function enableWhatsApp(bool $enabled = true): void
    {
        config(['services.whatsapp' => [
            'enabled' => $enabled,
            'token' => 'test-token',
            'phone_number_id' => '1234567890',
            'base_url' => 'https://graph.facebook.com',
            'api_version' => 'v23.0',
            'template_language' => 'en',
            'admin_contact' => 'Oneyard Admin on +2348000000000',
            'templates' => [
                'procurement_offer_accepted' => 'procurement_offer_accepted',
            ],
        ]]);
    }

    private function offer(?string $phone, string $status = ProcurementOffer::STATUS_SUBMITTED): ProcurementOffer
    {
        $procurement = Procurement::create([
            'created_by' => User::factory()->create()->id,
            'item_name' => 'School Uniform Fabric',
            'quantity' => 100,
            'unit' => 'Yard',
            'maximum_unit_price' => 3500,
            'commission_per_unit' => 100,
            'required_by' => now()->addDays(14)->toDateString(),
            'offer_deadline' => now()->addDays(7),
            'priority' => 'normal',
            'status' => Procurement::STATUS_READY,
        ]);

        return ProcurementOffer::create([
            'procurement_id' => $procurement->id,
            'user_id' => User::factory()->create(['phone' => $phone])->id,
            'quantity' => 80,
            'unit_price' => 3200,
            'total_price' => 256000,
            'status' => $status,
            'submitted_at' => now(),
        ]);
    }

    public function test_accepting_an_offer_queues_a_whatsapp_notification_when_enabled(): void
    {
        Mail::fake();
        $this->enableWhatsApp();
        Queue::fake();

        $offer = $this->offer('+2348012345678');

        $this->actingAs($this->admin())
            ->post(route('procurements.offers.accept', $offer))
            ->assertRedirect(route('procurements.show', $offer->procurement_id))
            ->assertSessionHas('success', fn ($message) => str_contains($message, 'WhatsApp notification has been queued'));

        Queue::assertPushed(
            SendProcurementOfferAcceptedWhatsApp::class,
            fn ($job) => $job->offerId === $offer->id
        );

        Mail::assertQueued(ProcurementOfferAccepted::class, function ($mail) use ($offer) {
            return $mail->offer->is($offer);
        });
    }

    public function test_no_notification_is_queued_when_whatsapp_is_disabled(): void
    {
        Mail::fake();
        $this->enableWhatsApp(false);
        Queue::fake();

        $offer = $this->offer('+2348012345678');

        $this->actingAs($this->admin())
            ->post(route('procurements.offers.accept', $offer))
            ->assertSessionHas('success', fn ($message) => str_contains($message, 'email notification has been queued'));

        Queue::assertNothingPushed();

        Mail::assertQueued(ProcurementOfferAccepted::class, function ($mail) use ($offer) {
            return $mail->offer->is($offer);
        });

        $this->assertDatabaseHas('procurement_offers', [
            'id' => $offer->id,
            'status' => ProcurementOffer::STATUS_ACCEPTED,
        ]);
    }

    public function test_acceptance_succeeds_but_warns_when_staff_has_no_phone_number(): void
    {
        Mail::fake();
        $this->enableWhatsApp();
        Queue::fake();

        $offer = $this->offer(null);

        $this->actingAs($this->admin())
            ->post(route('procurements.offers.accept', $offer))
            ->assertSessionHas('success', fn ($message) => str_contains($message, 'no phone number') && str_contains($message, 'email notification has been queued'));

        Queue::assertNothingPushed();

        Mail::assertQueued(ProcurementOfferAccepted::class, function ($mail) use ($offer) {
            return $mail->offer->is($offer);
        });

        $this->assertDatabaseHas('procurement_offers', [
            'id' => $offer->id,
            'status' => ProcurementOffer::STATUS_ACCEPTED,
        ]);
    }

    public function test_job_sends_the_template_message_with_offer_details(): void
    {
        $this->enableWhatsApp();
        Http::fake([
            'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.test']]], 200),
        ]);

        $offer = $this->offer('+2348012345678', ProcurementOffer::STATUS_ACCEPTED);

        (new SendProcurementOfferAcceptedWhatsApp($offer->id))
            ->handle(app(SendWhatsAppTemplate::class));

        Http::assertSent(function (Request $request) use ($offer) {
            $parameters = $request['template']['components'][0]['parameters'];

            return $request->url() === 'https://graph.facebook.com/v23.0/1234567890/messages'
                && $request->hasHeader('Authorization', 'Bearer test-token')
                && $request['messaging_product'] === 'whatsapp'
                && $request['to'] === '2348012345678'
                && $request['type'] === 'template'
                && $request['template']['name'] === 'procurement_offer_accepted'
                && $request['template']['language']['code'] === 'en'
                && count($parameters) === 9
                && $parameters[0]['text'] === $offer->user->name
                && $parameters[1]['text'] === 'School Uniform Fabric'
                && $parameters[2]['text'] === '80'
                && $parameters[3]['text'] === 'Yard'
                && $parameters[4]['text'] === '3,200.00'
                && $parameters[5]['text'] === '256,000.00'
                && $parameters[7]['text'] === 'Oneyard Admin on +2348000000000'
                && $parameters[8]['text'] === route('procurements.show', $offer->procurement_id);
        });
    }

    public function test_job_does_nothing_when_the_offer_is_no_longer_accepted(): void
    {
        $this->enableWhatsApp();
        Http::fake();

        $offer = $this->offer('+2348012345678', ProcurementOffer::STATUS_SUBMITTED);

        (new SendProcurementOfferAcceptedWhatsApp($offer->id))
            ->handle(app(SendWhatsAppTemplate::class));

        Http::assertNothingSent();
    }

    public function test_job_does_nothing_when_staff_has_no_phone_number(): void
    {
        $this->enableWhatsApp();
        Http::fake();

        $offer = $this->offer(null, ProcurementOffer::STATUS_ACCEPTED);

        (new SendProcurementOfferAcceptedWhatsApp($offer->id))
            ->handle(app(SendWhatsAppTemplate::class));

        Http::assertNothingSent();
    }

    public function test_job_fails_so_it_can_retry_when_the_api_rejects_the_request(): void
    {
        $this->enableWhatsApp();
        Http::fake([
            'graph.facebook.com/*' => Http::response(['error' => ['message' => 'Invalid parameter']], 400),
        ]);

        $offer = $this->offer('+2348012345678', ProcurementOffer::STATUS_ACCEPTED);

        $this->expectException(RequestException::class);

        (new SendProcurementOfferAcceptedWhatsApp($offer->id))
            ->handle(app(SendWhatsAppTemplate::class));
    }

    public function test_accepted_offer_email_renders_offer_details_and_procurement_link(): void
    {
        $offer = $this->offer(
            '+2348012345678',
            ProcurementOffer::STATUS_ACCEPTED
        );

        $html = (new ProcurementOfferAccepted($offer))->render();

        $this->assertStringContainsString('Your offer has been accepted', $html);
        $this->assertStringContainsString('School Uniform Fabric', $html);
        $this->assertStringContainsString('80 Yard', $html);
        $this->assertStringContainsString('₦3,200.00', $html);
        $this->assertStringContainsString('₦256,000.00', $html);
        $this->assertStringContainsString(
            $offer->procurement->required_by->format('d M Y'),
            $html
        );
        $this->assertStringContainsString(
            route('procurements.show', $offer->procurement_id),
            $html
        );
    }

}
