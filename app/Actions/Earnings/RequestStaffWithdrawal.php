<?php

namespace App\Actions\Earnings;

use App\Models\StaffPayoutAccount;
use App\Models\StaffWithdrawal;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RequestStaffWithdrawal
{
    public const MINIMUM_CENTS = 100000; // NGN 1,000.00

    public function execute(User $user, string $amount): StaffWithdrawal
    {
        $amount = trim($amount);

        if (! preg_match('/^\d{1,10}(\.\d{1,2})?$/', $amount)) {
            throw ValidationException::withMessages([
                'amount' => 'Enter a valid amount with at most two decimals.',
            ]);
        }

        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '0');
        $cents = ((int) $whole) * 100 + (int) str_pad($fraction, 2, '0');

        if ($cents < self::MINIMUM_CENTS) {
            throw ValidationException::withMessages([
                'amount' => 'The minimum withdrawal is NGN ' . number_format(self::MINIMUM_CENTS / 100, 2) . '.',
            ]);
        }

        return DB::transaction(function () use ($user, $cents) {
            // Serialise concurrent requests by the same staff member.
            User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();

            $account = StaffPayoutAccount::query()
                ->where('user_id', $user->id)
                ->where('is_active', true)
                ->first();

            if (! $account) {
                throw ValidationException::withMessages([
                    'account' => 'Add a payout account before requesting a withdrawal.',
                ]);
            }

            if (! $account->isUsable()) {
                throw ValidationException::withMessages([
                    'account' => 'Your payout account can be used from ' . $account->usable_from->format('d M Y H:i') . '.',
                ]);
            }

            $hasOpen = StaffWithdrawal::query()
                ->where('user_id', $user->id)
                ->whereIn('status', StaffWithdrawal::OPEN_STATUSES)
                ->exists();

            if ($hasOpen) {
                throw ValidationException::withMessages([
                    'amount' => 'You already have an open withdrawal request. Wait for it to be completed or cancel it.',
                ]);
            }

            if ($cents > StaffWithdrawal::availableCents($user->id)) {
                throw ValidationException::withMessages([
                    'amount' => 'The amount exceeds your available balance.',
                ]);
            }

            return StaffWithdrawal::query()->create([
                'user_id' => $user->id,
                'amount' => number_format($cents / 100, 2, '.', ''),
                'status' => StaffWithdrawal::STATUS_REQUESTED,
                'payout_account_id' => $account->id,
                'bank_name' => $account->bank_name,
                'account_name' => $account->account_name,
                'account_last_four' => $account->last_four,
                'requested_at' => now(),
            ]);
        });
    }
}
