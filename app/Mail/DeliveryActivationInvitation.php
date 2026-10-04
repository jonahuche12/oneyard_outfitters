<?php

namespace App\Mail;

use App\Models\Contact;
use App\Models\Delivery;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\SerializesModels;

class DeliveryActivationInvitation extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Delivery $delivery,
        public Contact $contact,
        public string $activationToken
    ) {
    }
    

    public function middleware(): array
    {
        return [
            (new RateLimited('mail'))->releaseAfter(2),
        ];
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Delivery Activation — ' . $this->delivery->order->order_number,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.deliveries.activation',
            with: [
                'delivery' => $this->delivery,
                'order' => $this->delivery->order,
                'contact' => $this->contact,
                'organization' => $this->delivery->order->organization,
                'url' => route(
                    'public.deliveries.activate',
                    $this->activationToken
                ),
            ],
        );
    }
}
