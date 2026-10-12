<?php

namespace App\Jobs;

use App\Actions\WhatsApp\SendWhatsAppTemplate;
use App\Models\Order;
use App\Models\OrderNotificationRecipient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\RequestException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendOrderApprovedWhatsApp implements ShouldQueue
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

        $recipient = OrderNotificationRecipient::query()
            ->with(['order.organization', 'contact'])
            ->find($this->recipientId);

        if (
            $recipient === null
            || $recipient->order === null
            || $recipient->contact === null
            || $recipient->order->status !== Order::STATUS_APPROVED
        ) {
            return;
        }

        $contact = $recipient->contact;
        $order = $recipient->order;
        $phone = filled($contact->whatsapp)
            ? $contact->whatsapp
            : $contact->phone;

        if (blank($phone)) {
            Log::warning(
                'Order approval WhatsApp notification skipped: contact has no phone number.',
                [
                    'order_id' => $order->id,
                    'recipient_id' => $recipient->id,
                    'contact_id' => $contact->id,
                ]
            );

            return;
        }

        $sendTemplate(
            $phone,
            (string) config('services.whatsapp.templates.order_approved'),
            [
                $contact->first_name,
                $order->order_number,
                $order->organization->name,
                route('public.orders.show', $recipient->access_token),
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
            'WhatsApp notification for approved order failed.',
            $context
        );
    }
}
