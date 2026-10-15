<?php

namespace App\Console\Commands;

use App\Actions\Earnings\UpdateEarningsActivityWeight;
use App\Models\EarningsActivityWeight;
use App\Models\User;
use Illuminate\Console\Command;

class SeedEarningsWeights extends Command
{
    protected $signature = 'earnings:seed-weights {--actor-email= : Active user holding earnings.weights.manage}';

    protected $description = 'Create the initial earnings activity weights through the audited action (existing weights are left untouched).';

    private const WEIGHTS = [
        'order_coordination' => ['Order coordination', '3'],
        'procurement' => ['Procurement and supplier coordination', '2'],
        'delivery' => ['Delivery and confirmation', '2'],
        'production_execution' => ['Production execution', '2'],
        'quality_control' => ['Quality inspection and approval', '1'],
        'management_oversight' => ['Management oversight', '1'],
    ];

    public function handle(UpdateEarningsActivityWeight $action): int
    {
        $email = $this->option('actor-email');

        $actor = $email
            ? User::query()->where('email', $email)->first()
            : User::query()
                ->where('is_active', true)
                ->whereHas('roles', fn ($q) => $q->where('slug', 'super-admin'))
                ->orderBy('id')
                ->first();

        if (! $actor) {
            $this->error('No actor found. Pass --actor-email=<active user with earnings.weights.manage>.');

            return self::FAILURE;
        }

        foreach (self::WEIGHTS as $key => [$label, $points]) {
            if (EarningsActivityWeight::query()->where('activity_key', $key)->exists()) {
                $this->line("Skipped {$key}: already configured.");

                continue;
            }

            $action->execute($actor, [
                'activity_key' => $key,
                'label' => $label,
                'points' => $points,
                'is_enabled' => true,
                'reason' => 'Initial weight from the approved draft schedule.',
            ]);

            $this->info("Created {$key} = {$points}");
        }

        return self::SUCCESS;
    }
}
