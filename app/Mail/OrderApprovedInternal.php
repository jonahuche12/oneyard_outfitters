<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\SerializesModels;

class OrderApprovedInternal extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Order $order
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
            subject: 'Order Approved — ' . $this->order->order_number,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.orders.approved-internal',
            with: [
                'order' => $this->order,
                'organization' => $this->order->organization,
                'url' => route('orders.show', $this->order),
            ],
        );
    }
}
