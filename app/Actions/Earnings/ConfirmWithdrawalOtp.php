<?php

namespace App\Actions\Earnings;

use App\Models\StaffPayoutAccount;
use App\Models\StaffWithdrawal;
use App\Models\User;
use App\Models\WithdrawalOtp;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ConfirmWithdrawalOtp
{
    public const MAX_ATTEMPTS = 5;

    public function __construct(private RequestStaffWithdrawal $requests)
    {
    }

    public function execute(User $user, string $code): StaffWithdrawal
    {
        $code = trim($code);

        if (! preg_match('/^\d{6}$/', $code)) {
            throw ValidationException::withMessages([
                'code' => 'Enter the 6-digit code.',
            ]);
        }

        // Return string failures so their changes commit before the exception is thrown.
        $outcome = DB::transaction(function () use ($user, $code) {
            $lockedUser = User::query()
                ->whereKey($user->id)
                ->lockForUpdate()
                ->firstOrFail();

            $otp = WithdrawalOtp::query()
                ->where('user_id', $lockedUser->id)
                ->whereNull('consumed_at')
                ->whereNull('invalidated_at')
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if (! $otp || $otp->expires_at->isPast()) {
                return 'This code has expired or is no longer valid. Please request a new one.';
            }

            $account = StaffPayoutAccount::query()
                ->where('user_id', $lockedUser->id)
                ->where('is_active', true)
                ->lockForUpdate()
                ->first();

            if (! $account || (int) $account->id !== (int) $otp->payout_account_id) {
                $otp->invalidated_at = now();
                $otp->save();

                return 'Your payout account changed. Please request a new code.';
            }

            if (! Hash::check($code, $otp->code_hash)) {
                $otp->attempts++;

                if ($otp->attempts >= self::MAX_ATTEMPTS) {
                    $otp->invalidated_at = now();
                    $message = 'Too many incorrect attempts. Please request a new code.';
                } else {
                    $message = 'That code is incorrect. '
                        . (self::MAX_ATTEMPTS - $otp->attempts)
                        . ' attempt(s) left.';
                }

                $otp->save();

                return $message;
            }

            // Re-check eligibility and register the withdrawal in the same transaction.
            // If another eligibility rule fails, transaction rollback leaves the code unconsumed.
            $withdrawal = $this->requests->execute(
                $lockedUser,
                number_format($otp->amount_cents / 100, 2, '.', ''),
                (int) $otp->payout_account_id,
            );

            $otp->consumed_at = now();
            $otp->save();

            return $withdrawal;
        });

        if (is_string($outcome)) {
            throw ValidationException::withMessages(['code' => $outcome]);
        }

        return $outcome;
    }
}
