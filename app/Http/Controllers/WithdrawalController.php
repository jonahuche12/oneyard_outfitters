<?php

namespace App\Http\Controllers;

use App\Actions\Earnings\RequestStaffWithdrawal;
use App\Models\StaffPayoutAccount;
use App\Models\StaffWalletEntry;
use App\Models\StaffWithdrawal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class WithdrawalController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        return view('wallet.withdrawals', [
            'ledger' => StaffWalletEntry::balanceFor($user),
            'available' => StaffWithdrawal::available($user->id),
            'account' => StaffPayoutAccount::query()->where('user_id', $user->id)->where('is_active', true)->first(),
            'withdrawals' => StaffWithdrawal::query()->where('user_id', $user->id)->latest('id')->paginate(15),
            'minimum' => RequestStaffWithdrawal::MINIMUM_CENTS / 100,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['amount' => ['required', 'string', 'max:20']]);

        app(RequestStaffWithdrawal::class)->execute($request->user(), $data['amount']);

        return redirect()->route('wallet.withdrawals')
            ->with('success', 'Your withdrawal request has been submitted for review.');
    }

    public function cancel(Request $request, StaffWithdrawal $withdrawal): RedirectResponse
    {
        abort_unless($withdrawal->user_id === $request->user()->id, 404);

        DB::transaction(function () use ($withdrawal) {
            $locked = StaffWithdrawal::query()->lockForUpdate()->findOrFail($withdrawal->id);

            if ($locked->status !== StaffWithdrawal::STATUS_REQUESTED) {
                throw ValidationException::withMessages([
                    'withdrawal' => 'Only a request that has not been reviewed can be cancelled.',
                ]);
            }

            $locked->update(['status' => StaffWithdrawal::STATUS_CANCELLED]);
        });

        return redirect()->route('wallet.withdrawals')->with('success', 'Your withdrawal request has been cancelled.');
    }
}
