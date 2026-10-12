<?php

namespace App\Jobs;

use App\Actions\WhatsApp\SendWhatsAppTemplate;
use App\Models\Quotation;
use App\Models\QuotationRecipient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\RequestException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendQuotationSentWhatsApp implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 3;

    public function __construct(public int $recipientId)
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

        $recipient = QuotationRecipient::query()
            ->with(['quotation.organization', 'contact'])
            ->find($this->recipientId);

        if (
            $recipient === null
            || $recipient->quotation === null
            || $recipient->contact === null
            || $recipient->quotation->status !== Quotation::STATUS_SENT
            || $recipient->sent_at === null
        ) {
            return;
        }

        $contact = $recipient->contact;
        $quotation = $recipient->quotation;
        $phone = filled($contact->whatsapp)
            ? $contact->whatsapp
            : $contact->phone;

        if (blank($phone)) {
            Log::warning(
                'Quotation WhatsApp notification skipped: contact has no phone number.',
                [
                    'quotation_id' => $quotation->id,
                    'recipient_id' => $recipient->id,
                    'contact_id' => $contact->id,
                ]
            );

            return;
        }

        $sendTemplate(
            $phone,
            (string) config('services.whatsapp.templates.quotation_sent'),
            [
                $contact->first_name,
                $quotation->quotation_number,
                $quotation->organization->name,
                route('public.quotations.show', $recipient->access_token),
            ]
        );
    }

    public function failed(Throwable $exception): void
    {
        $context = [
            'recipient_id' => $this->recipientId,
            'error' => $exception->getMessage(),
        ];

        if ($exception instanceof RequestException) {
            $context['status'] = $exception->response->status();
            $context['meta_error'] = $exception->response->json('error');
        }

        Log::error(
            'WhatsApp notification for sent quotation failed.',
            $context
        );
    }
}
