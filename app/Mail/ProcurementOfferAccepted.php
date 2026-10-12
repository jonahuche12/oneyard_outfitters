<?php

namespace App\Mail;

use App\Models\ProcurementOffer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\SerializesModels;

class ProcurementOfferAccepted extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public ProcurementOffer $offer)
    {
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
            subject: 'Procurement Offer Accepted — Oneyard Outfitters',
        );
    }

    public function content(): Content
    {
        $this->offer->loadMissing([
            'user:id,name,email',
            'procurement:id,item_name,unit,required_by',
        ]);

        return new Content(
            view: 'emails.procurement.offers.accepted',
            with: [
                'offer' => $this->offer,
                'procurement' => $this->offer->procurement,
                'staff' => $this->offer->user,
                'url' => route('procurements.show', $this->offer->procurement_id),
            ],
        );
    }
}
