<?php

namespace App\Actions\Stores;

use App\Models\StoreDailyCashRecord;
use App\Models\StoreRemittance;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReviewStoreRemittance
{
    public const PERMISSION = 'stores.remittances.review';

    public const DECISION_CONFIRM = 'confirm';
    public const DECISION_REJECT = 'reject';

    public function execute(User $actor, StoreRemittance $remittance, string $decision, ?string $reason = null): StoreRemittance
    {
        if (! $actor->is_active || ! $actor->hasPermission(self::PERMISSION)) {
            throw new AuthorizationException('You are not authorized to review store remittances.');
        }

        if (! in_array($decision, [self::DECISION_CONFIRM, self::DECISION_REJECT], true)) {
            throw ValidationException::withMessages([
                'decision' => 'Choose whether to confirm or reject this remission.',
            ]);
        }

        $reason = $reason === null ? null : trim($reason);
        $reason = $reason === '' ? null : $reason;

        if ($decision === self::DECISION_REJECT) {
            $length = $reason === null ? 0 : mb_strlen($reason);

            if ($length < 10 || $length > 1000) {
                throw ValidationException::withMessages([
                    'review_reason' => 'Give a reason of 10 to 1000 characters when rejecting a remission.',
                ]);
            }
        } elseif ($reason !== null && mb_strlen($reason) > 1000) {
            throw ValidationException::withMessages([
                'review_reason' => 'The note may not be longer than 1000 characters.',
            ]);
        }

        return DB::transaction(function () use ($actor, $remittance, $decision, $reason): StoreRemittance {
            // Same lock order as SubmitStoreRemittance: daily record, then remittance.
            $record = StoreDailyCashRecord::query()
                ->lockForUpdate()
                ->findOrFail($remittance->store_daily_cash_record_id);

            $locked = StoreRemittance::query()
                ->lockForUpdate()
                ->findOrFail($remittance->id);

            if ($locked->submitted_by === $actor->id) {
                throw new AuthorizationException('You cannot review a remission you submitted yourself.');
            }

            if ($locked->status !== StoreRemittance::STATUS_PENDING) {
                throw ValidationException::withMessages([
                    'status' => 'This remission has already been reviewed.',
                ]);
            }

            if ($decision === self::DECISION_CONFIRM) {
                $this->assertWithinClosingBalance($record);
            }

            $locked->forceFill([
                'status' => $decision === self::DECISION_CONFIRM
                    ? StoreRemittance::STATUS_CONFIRMED
                    : StoreRemittance::STATUS_REJECTED,
                'reviewed_by' => $actor->id,
                'reviewed_at' => now(),
                'review_reason' => $reason,
            ])->save();

            return $locked->refresh();
        });
    }

    /**
     * The submit action already enforces this. Checking again at confirmation
     * guards against any row that reached the table another way.
     */
    private function assertWithinClosingBalance(StoreDailyCashRecord $record): void
    {
        if ($record->closing_cash === null || $record->closing_bank === null) {
            throw ValidationException::withMessages([
                'status' => 'The business day for this remission is not closed.',
            ]);
        }

        $committedCash = '0.00';
        $committedBank = '0.00';

        StoreRemittance::query()
            ->where('store_daily_cash_record_id', $record->id)
            ->whereIn('status', [StoreRemittance::STATUS_PENDING, StoreRemittance::STATUS_CONFIRMED])
            ->get(['amount_cash', 'amount_bank'])
            ->each(function (StoreRemittance $row) use (&$committedCash, &$committedBank): void {
                $committedCash = bcadd($committedCash, (string) $row->amount_cash, 2);
                $committedBank = bcadd($committedBank, (string) $row->amount_bank, 2);
            });

        if (bccomp($committedCash, (string) $record->closing_cash, 2) > 0
            || bccomp($committedBank, (string) $record->closing_bank, 2) > 0) {
            throw ValidationException::withMessages([
                'status' => 'Confirming this remission would exceed the cash or bank closing balance for the day.',
            ]);
        }
    }
}
