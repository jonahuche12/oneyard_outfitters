<?php

namespace App\Jobs;

use App\Actions\WhatsApp\SendWhatsAppTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendStaffOnboardingWhatsApp implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function backoff(): array
    {
        return [30, 120];
    }

    public function __construct(
        public string $phone,
        public string $staffName,
        public string $setupUrl,
        public int $staffId,
    ) {
    }

    public function handle(SendWhatsAppTemplate $sender): void
    {
        if (! config('services.whatsapp.enabled')) {
            Log::info('Staff onboarding WhatsApp skipped because WhatsApp is disabled.', [
                'staff_id' => $this->staffId,
            ]);

            return;
        }

        $sender(
            $this->phone,
            config('services.whatsapp.templates.staff_onboarding'),
            [$this->staffName, $this->setupUrl],
        );
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Staff onboarding WhatsApp delivery failed after retries.', [
            'staff_id' => $this->staffId,
            'exception' => $exception->getMessage(),
        ]);
    }
}
