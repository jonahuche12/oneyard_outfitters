<?php

namespace App\Jobs;

use App\Mail\MeasurementLinkInvitation;
use App\Models\MeasurementLink;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

/**
 * Payload is encrypted so the raw token never sits in plain text in the
 * jobs or failed_jobs tables.
 */
class SendMeasurementLinkEmail implements ShouldQueue, ShouldBeEncrypted
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public function __construct(
        public MeasurementLink $link,
        public string $token,
        public ?string $correctionReason = null,
    ) {
    }

    public function backoff(): array
    {
        return [10, 30, 60, 120];
    }

    public function middleware(): array
    {
        return [(new RateLimited('mail'))->releaseAfter(2)];
    }

    public function handle(): void
    {
        $link = $this->link->fresh(['contact', 'order']);

        // Skip if revoked, replaced or already sent while waiting in the queue.
        if (! $link || $link->revoked_at !== null || $link->sent_at !== null || $link->expires_at->isPast()) {
            return;
        }

        $url = route('measurements.public.show', ['token' => $this->token]);

        Mail::to($link->contact->email)->send(new MeasurementLinkInvitation($link, $url, $this->correctionReason));

        $link->forceFill(['sent_at' => now()])->save();
    }
}
