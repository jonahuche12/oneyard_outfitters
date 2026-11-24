<?php

namespace App\Actions\Stores;

use App\Models\StoreFundAssignment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CorrectStoreFundAssignmentDispatch
{
    public function execute(
        StoreFundAssignment $assignment,
        array $data
    ): StoreFundAssignment {
        $validated = Validator::make($data, [
            'sent_cash' => [
                'required',
                'numeric',
                'min:0',
                'decimal:0,2',
            ],
            'sent_bank' => [
                'required',
                'numeric',
                'min:0',
                'decimal:0,2',
            ],
        ])->validate();

        $newCash = $this->toMinorUnits($validated['sent_cash']);
        $newBank = $this->toMinorUnits($validated['sent_bank']);

        if ($newCash === 0 && $newBank === 0) {
            throw ValidationException::withMessages([
                'sent_cash' => 'At least one dispatched amount must be greater than zero.',
            ]);
        }

        return DB::transaction(function () use (
            $assignment,
            $validated,
            $newCash,
            $newBank
        ) {
            $locked = StoreFundAssignment::query()
                ->lockForUpdate()
                ->findOrFail($assignment->id);

            Gate::authorize('correctDispatch', $locked);

            if ($locked->status !== StoreFundAssignment::STATUS_SENT) {
                throw ValidationException::withMessages([
                    'status' => 'Only dispatched fund assignments can be corrected.',
                ]);
            }

            $previousCash = $locked->sent_cash;
            $previousBank = $locked->sent_bank;

            if (
                $newCash === $this->toMinorUnits($previousCash)
                && $newBank === $this->toMinorUnits($previousBank)
            ) {
                throw ValidationException::withMessages([
                    'sent_cash' => 'The corrected amounts must differ from the current dispatched amounts.',
                ]);
            }

            $actorId = Auth::id();
            $correctedAt = now();

            $locked->forceFill([
                'sent_cash' => $validated['sent_cash'],
                'sent_bank' => $validated['sent_bank'],
            ])->save();

            $locked->events()->create([
                'actor_id' => $actorId,
                'event_type' => 'dispatch_corrected',
                'details' => [
                    'previous_sent_cash' => $previousCash,
                    'previous_sent_bank' => $previousBank,
                    'corrected_sent_cash' => $locked->sent_cash,
                    'corrected_sent_bank' => $locked->sent_bank,
                    'dispatched_by' => $locked->dispatched_by,
                    'corrected_by' => $actorId,
                    'corrected_at' => $correctedAt->toISOString(),
                ],
            ]);

            return $locked->refresh();
        });
    }

    /**
     * Convert a validated decimal amount into integer minor units
     * without relying on floating-point equality.
     */
    private function toMinorUnits(mixed $amount): int
    {
        $value = trim((string) $amount);

        if (! preg_match('/^\+?(\d+)(?:\.(\d{1,2}))?$/D', $value, $matches)) {
            throw ValidationException::withMessages([
                'amount' => 'The amount must have no more than two decimal places.',
            ]);
        }

        $whole = (int) $matches[1];
        $fraction = (int) str_pad($matches[2] ?? '', 2, '0');

        return ($whole * 100) + $fraction;
    }
}
