<?php

namespace App\Actions\Earnings;

use App\Models\EarningsActivityWeight;
use App\Models\OrderEarningsPool;
use App\Models\User;
use LogicException;

/**
 * Pure, read-only allocation of one earnings pool.
 *
 * Returns a verdict ('allocatable' or 'held') and proposed per-staff amounts.
 * Nothing is written: persisting a held status, oversight rows and ledger
 * entries is the responsibility of the finance finalization step.
 */
class CalculateOrderEarningsAllocation
{
    public const OVERSIGHT_ACTIVITY_KEY = 'management_oversight';

    public const VERDICT_ALLOCATABLE = 'allocatable';
    public const VERDICT_HELD = 'held';

    private const POINT_SCALE = 10000;

    public function execute(OrderEarningsPool $pool): array
    {
        if ($pool->status === OrderEarningsPool::STATUS_FINALIZED) {
            throw new LogicException(
                'A finalized earnings pool cannot be recalculated.'
            );
        }

        $contributions = $pool->contributions()->active()->with('user')->get();
        $poolCents = $this->toCents((string) $pool->pool_amount);

        if ($contributions->isEmpty()) {
            return $this->held($poolCents, ['no_contributions']);
        }

        $inactive = $contributions
            ->filter(fn ($c) => ! $c->user || ! $c->user->is_active)
            ->pluck('user_id')
            ->unique()
            ->sort()
            ->values()
            ->all();

        if ($inactive !== []) {
            return $this->held($poolCents, ['inactive_contributors'], $inactive);
        }

        // Group sizes per activity (ordinary contributions).
        $groupSizes = $contributions
            ->groupBy('activity_key')
            ->map(fn ($rows) => $rows->count());

        // Derived management oversight.
        $oversightWeight = EarningsActivityWeight::query()
            ->where('activity_key', self::OVERSIGHT_ACTIVITY_KEY)
            ->where('is_enabled', true)
            ->first();

        $oversightUserIds = [];

        if ($oversightWeight) {
            $oversightUserIds = User::query()
                ->where('is_active', true)
                ->whereHas('roles', fn ($q) => $q->where('slug', 'super-admin'))
                ->orderBy('id')
                ->pluck('id')
                ->all();
        }

        $oversightSize = count($oversightUserIds);

        $denominator = 1;
        foreach ($groupSizes as $size) {
            $denominator = $this->lcm($denominator, (int) $size);
        }
        if ($oversightSize > 0) {
            $denominator = $this->lcm($denominator, $oversightSize);
        }

        // Integer weight per staff member, scaled by $denominator * POINT_SCALE.
        $weights = [];
        $breakdown = [];

        foreach ($contributions as $contribution) {
            $size = (int) $groupSizes[$contribution->activity_key];
            $units = $this->pointsToUnits((string) $contribution->points);
            $weight = $units * intdiv($denominator, $size);

            $weights[$contribution->user_id] =
                ($weights[$contribution->user_id] ?? 0) + $weight;

            $breakdown[] = [
                'user_id' => $contribution->user_id,
                'activity_key' => $contribution->activity_key,
                'source_type' => $contribution->source_type,
                'points' => (string) $contribution->points,
                'shared_with' => $size,
            ];
        }

        if ($oversightSize > 0) {
            $units = $this->pointsToUnits((string) $oversightWeight->points);

            foreach ($oversightUserIds as $userId) {
                $weight = $units * intdiv($denominator, $oversightSize);

                $weights[$userId] = ($weights[$userId] ?? 0) + $weight;

                $breakdown[] = [
                    'user_id' => $userId,
                    'activity_key' => self::OVERSIGHT_ACTIVITY_KEY,
                    'source_type' => 'oversight',
                    'points' => (string) $oversightWeight->points,
                    'shared_with' => $oversightSize,
                ];
            }
        }

        $totalWeight = array_sum($weights);

        if ($totalWeight <= 0) {
            return $this->held($poolCents, ['zero_points']);
        }

        if ($poolCents > 0
            && max($weights) > intdiv(PHP_INT_MAX, $poolCents)) {
            throw new LogicException(
                'Earnings allocation exceeds safe integer range.'
            );
        }

        ksort($weights);

        $floors = [];
        $remainders = [];

        foreach ($weights as $userId => $weight) {
            $product = $poolCents * $weight;
            $floors[$userId] = intdiv($product, $totalWeight);
            $remainders[$userId] = $product % $totalWeight;
        }

        $leftover = $poolCents - array_sum($floors);

        $order = array_keys($weights);
        usort($order, function ($a, $b) use ($remainders) {
            return [$remainders[$b], $a] <=> [$remainders[$a], $b];
        });

        foreach (array_slice($order, 0, $leftover) as $userId) {
            $floors[$userId]++;
        }

        $scale = $denominator * self::POINT_SCALE;

        $allocations = [];
        foreach ($weights as $userId => $weight) {
            $allocations[] = [
                'user_id' => $userId,
                'amount' => $this->fromCents($floors[$userId]),
                'weight' => $weight,
                'weight_scale' => $scale,
                'effective_points' => number_format($weight / $scale, 4, '.', ''),
            ];
        }

        return [
            'verdict' => self::VERDICT_ALLOCATABLE,
            'hold_reasons' => [],
            'inactive_user_ids' => [],
            'pool_amount' => $this->fromCents($poolCents),
            'total_weight' => $totalWeight,
            'weight_scale' => $scale,
            'allocations' => $allocations,
            'breakdown' => $breakdown,
        ];
    }

    private function held(int $poolCents, array $reasons, array $inactive = []): array
    {
        return [
            'verdict' => self::VERDICT_HELD,
            'hold_reasons' => $reasons,
            'inactive_user_ids' => $inactive,
            'pool_amount' => $this->fromCents($poolCents),
            'total_weight' => 0,
            'weight_scale' => self::POINT_SCALE,
            'allocations' => [],
            'breakdown' => [],
        ];
    }

    private function pointsToUnits(string $points): int
    {
        return (int) str_replace(
            '.',
            '',
            number_format((float) $points, 4, '.', '')
        );
    }

    private function toCents(string $amount): int
    {
        return (int) str_replace(
            '.',
            '',
            number_format((float) $amount, 2, '.', '')
        );
    }

    private function fromCents(int $cents): string
    {
        return number_format($cents / 100, 2, '.', '');
    }

    private function gcd(int $a, int $b): int
    {
        while ($b !== 0) {
            [$a, $b] = [$b, $a % $b];
        }

        return $a;
    }

    private function lcm(int $a, int $b): int
    {
        return intdiv($a, $this->gcd($a, $b)) * $b;
    }
}
