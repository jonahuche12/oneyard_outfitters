<?php

namespace App\Actions\Earnings;

use App\Models\StaffWalletEntry;
use App\Models\StaffWithdrawal;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ManageStaffWithdrawal
{
    public const PERMISSION = 'earnings.withdrawals.manage';

    public function approve(
        StaffWithdrawal $withdrawal,
        User $actor,
        string $reason
    ): StaffWithdrawal {
        $this->authorize($actor);

        $reason = trim($reason);

        if (mb_strlen($reason) < 10 || mb_strlen($reason) > 1000) {
            throw ValidationException::withMessages([
                'approval_reason' => 'Provide an approval reason between 10 and 1,000 characters.',
            ]);
        }

        return DB::transaction(function () use ($withdrawal, $actor, $reason) {
            $locked = StaffWithdrawal::query()
                ->lockForUpdate()
                ->findOrFail($withdrawal->id);

            $this->assertNotOwnRequest($locked, $actor);
            $this->assertStatus($locked, 'requested');

            $locked->update([
                'status' => 'approved',
                'reviewed_by' => $actor->id,
                'reviewed_at' => now(),
                'reject_reason' => null,
                'notes' => $reason,
            ]);

            return $locked->fresh(['user']);
        });
    }

    public function reject(
        StaffWithdrawal $withdrawal,
        User $actor,
        string $reason
    ): StaffWithdrawal {
        $this->authorize($actor);

        $reason = trim($reason);

        if (mb_strlen($reason) < 10 || mb_strlen($reason) > 1000) {
            throw ValidationException::withMessages([
                'reject_reason' => 'Provide a rejection reason between 10 and 1,000 characters.',
            ]);
        }

        return DB::transaction(function () use ($withdrawal, $actor, $reason) {
            $locked = StaffWithdrawal::query()
                ->lockForUpdate()
                ->findOrFail($withdrawal->id);

            $this->assertNotOwnRequest($locked, $actor);
            $this->assertStatus($locked, 'requested');

            $locked->update([
                'status' => 'rejected',
                'reviewed_by' => $actor->id,
                'reviewed_at' => now(),
                'reject_reason' => $reason,
            ]);

            return $locked->fresh(['user']);
        });
    }

    public function markPaid(
        StaffWithdrawal $withdrawal,
        User $actor,
        string $paymentReference
    ): StaffWithdrawal {
        $this->authorize($actor);

        $paymentReference = trim($paymentReference);

        if (mb_strlen($paymentReference) < 3 || mb_strlen($paymentReference) > 120) {
            throw ValidationException::withMessages([
                'payment_reference' => 'Enter a payment reference between 3 and 120 characters.',
            ]);
        }

        return DB::transaction(function () use ($withdrawal, $actor, $paymentReference) {
            $locked = StaffWithdrawal::query()
                ->lockForUpdate()
                ->findOrFail($withdrawal->id);

            $this->assertNotOwnRequest($locked, $actor);
            $this->assertStatus($locked, 'approved');

            StaffWalletEntry::query()->create([
                'user_id' => $locked->user_id,
                'type' => StaffWalletEntry::TYPE_WITHDRAWAL,
                'amount' => number_format(
                    -StaffWithdrawal::cents($locked->amount) / 100,
                    2,
                    '.',
                    ''
                ),
                'reason' => 'Withdrawal paid. Reference: '.$paymentReference,
                'created_by' => $actor->id,
            ]);

            $locked->update([
                'status' => 'paid',
                'paid_by' => $actor->id,
                'paid_at' => now(),
                'payment_reference' => $paymentReference,
            ]);

            return $locked->fresh(['user']);
        });
    }

    private function authorize(User $actor): void
    {
        if (! $actor->is_active || ! $actor->hasPermission(self::PERMISSION)) {
            throw new AuthorizationException(
                'You are not authorized to manage withdrawal requests.'
            );
        }
    }

    private function assertNotOwnRequest(
        StaffWithdrawal $withdrawal,
        User $actor
    ): void {
        if ((int) $withdrawal->user_id === (int) $actor->id) {
            throw ValidationException::withMessages([
                'withdrawal' => 'You cannot manage your own withdrawal request.',
            ]);
        }
    }

    private function assertStatus(
        StaffWithdrawal $withdrawal,
        string $expected
    ): void {
        if ($withdrawal->status !== $expected) {
            throw ValidationException::withMessages([
                'withdrawal' => 'This request is no longer eligible for that action. Refresh the page.',
            ]);
        }
    }
}
