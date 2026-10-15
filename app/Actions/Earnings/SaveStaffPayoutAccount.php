<?php

namespace App\Actions\Earnings;

use App\Models\StaffPayoutAccount;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SaveStaffPayoutAccount
{
    public const WAIT_HOURS = 24;

    /**
     * Deactivates the user's current account (history kept) and creates a new
     * active one that becomes usable after the waiting period.
     */
    public function execute(User $user, array $input): StaffPayoutAccount
    {
        $data = Validator::make($input, [
            'bank_code' => ['required', 'string', 'in:' . implode(',', array_keys(config('banks')))],
            'account_name' => ['required', 'string', 'min:3', 'max:120'],
            'account_number' => ['required', 'regex:/^\d{10}$/'],
        ], [
            'account_number.regex' => 'The account number must be exactly 10 digits.',
            'bank_code.in' => 'Please choose a bank from the list.',
        ])->validate();

        return DB::transaction(function () use ($user, $data) {
            StaffPayoutAccount::query()
                ->where('user_id', $user->id)
                ->where('is_active', true)
                ->lockForUpdate()
                ->update(['is_active' => false]);

            return StaffPayoutAccount::query()->create([
                'user_id' => $user->id,
                'bank_code' => $data['bank_code'],
                'bank_name' => config('banks')[$data['bank_code']],
                'account_name' => trim($data['account_name']),
                'account_number' => $data['account_number'],
                'last_four' => substr($data['account_number'], -4),
                'is_active' => true,
                'usable_from' => now()->addHours(self::WAIT_HOURS),
            ]);
        });
    }
}
