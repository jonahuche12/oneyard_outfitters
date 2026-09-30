<?php

namespace App\Mail;

use App\Models\QuotationRecipient;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class QuotationInvitation extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public QuotationRecipient $recipient
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Quotation ' . $this->recipient->quotation->quotation_number,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.quotations.invitation',
            with: [
                'recipient' => $this->recipient,
                'quotation' => $this->recipient->quotation,
                'url' => route(
                    'public.quotations.show',
                    $this->recipient->access_token
                ),
            ],
        );
    }
}
