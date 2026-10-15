<?php

namespace App\Actions\Earnings;

use App\Models\OrderEarningsContribution;
use App\Models\OrderEarningsPool;
use App\Models\ProcurementOffer;
use App\Models\QualityControlInspection;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Derives workflow contributions for a pool from authoritative order records.
 * Idempotent. Nothing is dropped silently: ineligible staff are reported.
 */
class DeriveOrderEarningsContributions
{
    public const COORDINATION = 'order_coordination';
    public const QUALITY_CONTROL = 'quality_control';
    public const DELIVERY = 'delivery';
    public const PROCUREMENT = 'procurement';

    public function __construct(
        private RecordOrderEarningsContribution $recorder
    ) {
    }

    /**
     * @return array{credited: array<int, array<string, mixed>>, skipped: array<int, array<string, mixed>>}
     */
    public function execute(OrderEarningsPool $pool): array
    {
        if ($pool->status === OrderEarningsPool::STATUS_FINALIZED) {
            throw ValidationException::withMessages([
                'pool' => 'Contributions cannot be derived for a finalized earnings pool.',
            ]);
        }

        $credited = [];
        $skipped = [];

        foreach ($this->candidates((int) $pool->order_id) as $candidate) {
            $base = [
                'user_id' => $candidate['user_id'],
                'activity_key' => $candidate['activity_key'],
                'source_reference' => $candidate['source_reference'],
            ];

            $scope = fn () => OrderEarningsContribution::query()
                ->where('order_earnings_pool_id', $pool->id)
                ->where('user_id', $candidate['user_id'])
                ->where('activity_key', $candidate['activity_key']);

            if ($scope()->whereNotNull('voided_at')->where('source_type', OrderEarningsContribution::SOURCE_WORKFLOW)->exists()
                && ! $scope()->active()->exists()) {
                $skipped[] = $base + ['reason' => 'voided by finance'];

                continue;
            }

            try {
                $row = $this->recorder->execute($pool, [
                    'user_id' => $candidate['user_id'],
                    'activity_key' => $candidate['activity_key'],
                    'source_type' => OrderEarningsContribution::SOURCE_WORKFLOW,
                    'source_reference' => $candidate['source_reference'],
                    'activity_at' => $candidate['activity_at']->toDateTimeString(),
                ]);
            } catch (ValidationException $e) {
                $skipped[] = $base + [
                    'reason' => (string) collect($e->errors())->flatten()->first(),
                ];

                continue;
            }

            if ($row->source_type === OrderEarningsContribution::SOURCE_WORKFLOW
                && $row->metadata !== $candidate['metadata']) {
                $row->update(['metadata' => $candidate['metadata']]);
            }

            $credited[] = $base;
        }

        return ['credited' => $credited, 'skipped' => $skipped];
    }

    /**
     * One candidate per (activity, user), keeping the earliest proof time and
     * every proving record in metadata.
     *
     * @return array<int, array<string, mixed>>
     */
    private function candidates(int $orderId): array
    {
        $found = [];

        $add = function (
            string $activity,
            mixed $userId,
            ?string $at,
            string $table,
            int $recordId,
            string $role
        ) use (&$found): void {
            if (! $userId || ! $at) {
                return;
            }

            $userId = (int) $userId;
            $time = Carbon::parse($at);
            $key = $activity . '|' . $userId;
            $reference = \Illuminate\Support\Str::singular($table) . ':' . $recordId;

            if (! isset($found[$key])) {
                $found[$key] = [
                    'user_id' => $userId,
                    'activity_key' => $activity,
                    'source_reference' => $reference,
                    'activity_at' => $time,
                    'metadata' => ['records' => []],
                ];
            } elseif ($time->lt($found[$key]['activity_at'])) {
                $found[$key]['activity_at'] = $time;
                $found[$key]['source_reference'] = $reference;
            }

            $found[$key]['metadata']['records'][] = [
                'table' => $table,
                'id' => $recordId,
                'role' => $role,
            ];
        };

        foreach (DB::table('order_assignments')
            ->where('order_id', $orderId)
            ->orderBy('assigned_at')->orderBy('id')->get() as $r) {
            $add(self::COORDINATION, $r->user_id, $r->assigned_at, 'order_assignments', $r->id, 'coordinator');
        }

        foreach (DB::table('quality_control_inspections')
            ->where('order_id', $orderId)
            ->where('status', QualityControlInspection::STATUS_COMPLETED)
            ->orderBy('id')->get() as $r) {
            $add(self::QUALITY_CONTROL, $r->inspected_by, $r->inspected_at ?? $r->approved_at ?? $r->updated_at, 'quality_control_inspections', $r->id, 'inspector');
            $add(self::QUALITY_CONTROL, $r->approved_by, $r->approved_at ?? $r->inspected_at ?? $r->updated_at, 'quality_control_inspections', $r->id, 'approver');
        }

        foreach (DB::table('deliveries')->where('order_id', $orderId)->get() as $r) {
            $add(self::DELIVERY, $r->created_by, $r->created_at, 'deliveries', $r->id, 'started');
            $add(self::DELIVERY, $r->confirmed_by, $r->confirmed_at ?? $r->updated_at, 'deliveries', $r->id, 'confirmed');
        }

        $procurementIds = DB::table('procurements')
            ->where('order_id', $orderId)
            ->whereNull('deleted_at')
            ->pluck('id');

        if ($procurementIds->isNotEmpty()) {
            foreach (DB::table('procurement_offers')
                ->whereIn('procurement_id', $procurementIds)
                ->whereIn('status', [ProcurementOffer::STATUS_ACCEPTED, ProcurementOffer::STATUS_DELIVERED])
                ->whereNotNull('delivered_at')
                ->where('delivered_quantity', '>', 0)
                ->orderBy('delivered_at')->orderBy('id')->get() as $r) {
                $add(self::PROCUREMENT, $r->user_id, $r->delivered_at, 'procurement_offers', $r->id, 'sourcing');
            }
        }

        return array_values($found);
    }
}
