<?php

namespace App\Jobs;

use App\Actions\WhatsApp\SendWhatsAppTemplate;
use App\Models\ProcurementOffer;
use Illuminate\Bus\Queueable;
use Illuminate\Http\Client\RequestException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendProcurementOfferAcceptedWhatsApp implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 3;

    public function __construct(public int $offerId)
    {
    }

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [30, 120];
    }

    public function handle(SendWhatsAppTemplate $sendTemplate): void
    {
        if (! config('services.whatsapp.enabled')) {
            return;
        }

        $offer = ProcurementOffer::query()
            ->with([
                'procurement:id,item_name,unit,required_by',
                'user:id,name,phone',
            ])
            ->find($this->offerId);

        // The offer may have been removed or changed since it was queued.
        if ($offer === null || $offer->status !== ProcurementOffer::STATUS_ACCEPTED) {
            return;
        }

        $phone = $offer->user?->phone;

        if (blank($phone)) {
            Log::warning('WhatsApp notification skipped: staff member has no phone number.', [
                'offer_id' => $offer->id,
            ]);

            return;
        }

        $procurement = $offer->procurement;

        $sendTemplate(
            $phone,
            (string) config('services.whatsapp.templates.procurement_offer_accepted'),
            [
                $offer->user->name,
                $procurement->item_name,
                rtrim(rtrim(number_format((float) $offer->quantity, 2, '.', ''), '0'), '.'),
                $procurement->unit,
                number_format((float) $offer->unit_price, 2),
                number_format((float) $offer->total_price, 2),
                $procurement->required_by?->format('d M Y') ?? 'Not specified',
                config('services.whatsapp.admin_contact') ?: 'the Oneyard admin',
                route('procurements.show', $procurement->id),
            ]
        );
    }

    public function failed(Throwable $exception): void
    {
        $context = [
            'offer_id' => $this->offerId,
            'error' => $exception->getMessage(),
        ];

        if ($exception instanceof RequestException) {
            $context['status'] = $exception->response->status();
            $context['meta_error'] = $exception->response->json('error');
        }

        Log::error('WhatsApp notification for accepted procurement offer failed.', $context);
    }
}
