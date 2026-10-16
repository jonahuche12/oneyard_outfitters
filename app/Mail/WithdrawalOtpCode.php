<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Deliberately NOT queued: the code must arrive immediately and must not depend on a queue worker.
 */
class WithdrawalOtpCode extends Mailable
{
    public function __construct(
        public string $name,
        public string $code,
        public string $amount,
        public int $minutes,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your Oneyard withdrawal code');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.withdrawals.otp');
    }
}
