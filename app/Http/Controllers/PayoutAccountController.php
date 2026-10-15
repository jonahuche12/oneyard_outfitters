<?php

namespace App\Http\Controllers;

use App\Actions\Earnings\SaveStaffPayoutAccount;
use App\Models\StaffPayoutAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PayoutAccountController extends Controller
{
    public function show(Request $request): View
    {
        $account = StaffPayoutAccount::query()
            ->where('user_id', $request->user()->id)
            ->where('is_active', true)
            ->first();

        return view('wallet.payout-account', [
            'account' => $account,
            'banks' => config('banks'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        app(SaveStaffPayoutAccount::class)->execute(
            $request->user(),
            $request->only(['bank_code', 'account_name', 'account_number'])
        );

        return redirect()->route('wallet.payout-account')
            ->with('success', 'Your payout account has been saved. It can be used for withdrawals after '
                . SaveStaffPayoutAccount::WAIT_HOURS . ' hours.');
    }
}
