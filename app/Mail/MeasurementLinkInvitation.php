<?php

namespace App\Mail;

use App\Models\MeasurementLink;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Deliberately NOT queued: the raw token must never be written to the
 * jobs or failed_jobs tables.
 */
class MeasurementLinkInvitation extends Mailable
{
    public function __construct(
        public MeasurementLink $link,
        public string $measurementUrl,
        public ?string $correctionReason = null,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->correctionReason
            ? 'Please correct your measurements: Oneyard Outfitters'
            : 'Measurements needed for your Oneyard Outfitters order');
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.measurements.invitation',
            with: [
                'contact' => $this->link->contact,
                'order' => $this->link->order,
                'measurementUrl' => $this->measurementUrl,
                'expiresAt' => $this->link->expires_at,
                'correctionReason' => $this->correctionReason,
            ],
        );
    }
}
