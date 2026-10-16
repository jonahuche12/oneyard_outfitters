<?php

namespace App\Actions\Earnings;

use App\Mail\WithdrawalOtpCode;
use App\Models\User;
use App\Models\WithdrawalOtp;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Throwable;

class IssueWithdrawalOtp
{
    public const TTL_MINUTES = 10;
    public const COOLDOWN_SECONDS = 60;
    public const MAX_SENDS_PER_HOUR = 3;

    public function __construct(
        private RequestStaffWithdrawal $requests,
    ) {
    }

    /**
     * @return array{otp: WithdrawalOtp, channels: array<int, string>}
     */
    public function execute(User $user, string $amount): array
    {
        $cents = RequestStaffWithdrawal::parseCents($amount);

        $result = DB::transaction(function () use ($user, $cents) {
            // Serialize issuance, cooldown checks, and hourly limits per user.
            $lockedUser = User::query()
                ->whereKey($user->id)
                ->lockForUpdate()
                ->firstOrFail();

            $account = $this->requests->assertEligible($lockedUser, $cents);

            $latest = WithdrawalOtp::query()
                ->where('user_id', $lockedUser->id)
                ->latest('id')
                ->first();

            if ($latest && $latest->created_at->gt(now()->subSeconds(self::COOLDOWN_SECONDS))) {
                throw ValidationException::withMessages([
                    'otp' => 'Please wait a minute before requesting another code.',
                ]);
            }

            $sentLastHour = WithdrawalOtp::query()
                ->where('user_id', $lockedUser->id)
                ->where('created_at', '>=', now()->subHour())
                ->count();

            if ($sentLastHour >= self::MAX_SENDS_PER_HOUR) {
                throw ValidationException::withMessages([
                    'otp' => 'Too many codes requested. Please try again later.',
                ]);
            }

            $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

            // Supersede older codes inside the same transaction.
            WithdrawalOtp::query()
                ->where('user_id', $lockedUser->id)
                ->whereNull('consumed_at')
                ->whereNull('invalidated_at')
                ->update(['invalidated_at' => now()]);

            $otp = WithdrawalOtp::query()->create([
                'user_id' => $lockedUser->id,
                'payout_account_id' => $account->id,
                'amount_cents' => $cents,
                'code_hash' => Hash::make($code),
                'expires_at' => now()->addMinutes(self::TTL_MINUTES),
                'attempts' => 0,
                'sent_at' => now(),
            ]);

            // Email only. If sending fails, throw so the OTP transaction rolls back.
            try {
                Mail::to($lockedUser->email)->send(new WithdrawalOtpCode(
                    $lockedUser->name,
                    $code,
                    number_format($cents / 100, 2),
                    self::TTL_MINUTES,
                ));
            } catch (Throwable $e) {
                Log::warning('Withdrawal OTP email failed.', [
                    'user_id' => $lockedUser->id,
                    'exception' => $e->getMessage(),
                ]);

                throw ValidationException::withMessages([
                    'otp' => 'We could not deliver a code right now. Please try again later or contact an administrator.',
                ]);
            }

            return ['otp' => $otp, 'channels' => ['email']];
        });

        return $result;
    }
}
