<?php

namespace App\Mail;

use App\Models\StaffWithdrawal;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class WithdrawalStatusUpdated extends Mailable
{
    public function __construct(
        public StaffWithdrawal $withdrawal,
        public string $walletUrl,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Withdrawal Update — Oneyard Outfitters',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.withdrawals.status-updated'
        );
    }
}
