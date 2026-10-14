<?php

namespace App\Actions\Earnings;

use App\Models\StaffWalletEntry;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdjustStaffWallet
{
    public const PERMISSION = 'earnings.adjustments.create';

    /**
     * Record a signed manual adjustment. Never edits existing entries.
     */
    public function adjust(
        User $target,
        string $amount,
        string $reason,
        User $actor,
        ?int $poolId = null
    ): StaffWalletEntry {
        $this->authorize($actor);

        $amount = trim($amount);
        $reason = $this->validReason($reason);

        if (! preg_match('/^-?\d{1,13}(\.\d{1,2})?$/', $amount)
            || (float) $amount === 0.0) {
            throw ValidationException::withMessages([
                'amount' => 'The adjustment must be a non-zero amount with at most two decimals.',
            ]);
        }

        return StaffWalletEntry::query()->create([
            'user_id' => $target->id,
            'type' => StaffWalletEntry::TYPE_ADJUSTMENT,
            'amount' => number_format((float) $amount, 2, '.', ''),
            'order_earnings_pool_id' => $poolId,
            'reason' => $reason,
            'created_by' => $actor->id,
        ]);
    }

    /**
     * Reverse an earning entry once with an equal and opposite entry.
     */
    public function reverse(
        StaffWalletEntry $entry,
        string $reason,
        User $actor
    ): StaffWalletEntry {
        $this->authorize($actor);

        $reason = $this->validReason($reason);

        return DB::transaction(function () use ($entry, $reason, $actor) {
            $locked = StaffWalletEntry::query()
                ->lockForUpdate()
                ->findOrFail($entry->id);

            if ($locked->type !== StaffWalletEntry::TYPE_EARNING) {
                throw ValidationException::withMessages([
                    'entry' => 'Only earning entries can be reversed.',
                ]);
            }

            if ((float) $locked->amount === 0.0) {
                throw ValidationException::withMessages([
                    'entry' => 'A zero-value entry cannot be reversed.',
                ]);
            }

            $alreadyReversed = StaffWalletEntry::query()
                ->where('reverses_entry_id', $locked->id)
                ->exists();

            if ($alreadyReversed) {
                throw ValidationException::withMessages([
                    'entry' => 'This entry has already been reversed.',
                ]);
            }

            return StaffWalletEntry::query()->create([
                'user_id' => $locked->user_id,
                'type' => StaffWalletEntry::TYPE_REVERSAL,
                'amount' => number_format(-1 * (float) $locked->amount, 2, '.', ''),
                'order_earnings_pool_id' => $locked->order_earnings_pool_id,
                'reverses_entry_id' => $locked->id,
                'reason' => $reason,
                'created_by' => $actor->id,
            ]);
        });
    }

    private function authorize(User $actor): void
    {
        if (! $actor->is_active || ! $actor->hasPermission(self::PERMISSION)) {
            throw new AuthorizationException(
                'You are not authorized to adjust staff wallets.'
            );
        }
    }

    private function validReason(string $reason): string
    {
        $reason = trim($reason);

        if (mb_strlen($reason) < 10 || mb_strlen($reason) > 1000) {
            throw ValidationException::withMessages([
                'reason' => 'A reason of 10 to 1000 characters is required.',
            ]);
        }

        return $reason;
    }
}
