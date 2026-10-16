<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class WithdrawalRequestReceived extends Mailable
{
    public function __construct(
        public string $name,
        public string $amount,
        public string $walletUrl,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Withdrawal Request Received — Oneyard Outfitters',
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.withdrawals.request-received');
    }
}
