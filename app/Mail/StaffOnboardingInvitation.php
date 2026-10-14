<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\SerializesModels;

class StaffOnboardingInvitation extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $staff,
        public string $setupUrl,
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
            subject: 'Set Up Your Oneyard Outfitters Staff Account',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.staff.onboarding',
            with: [
                'staff' => $this->staff,
                'setupUrl' => $this->setupUrl,
            ],
        );
    }
}
