<?php

namespace App\Http\Controllers;

use App\Actions\Earnings\RequestStaffWithdrawal;
use App\Actions\Earnings\IssueWithdrawalOtp;
use App\Actions\Earnings\ConfirmWithdrawalOtp;
use App\Mail\WithdrawalRequestReceived;
use App\Models\WithdrawalOtp;
use App\Models\StaffPayoutAccount;
use App\Models\StaffWalletEntry;
use App\Models\StaffWithdrawal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;
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
            'pendingOtp' => WithdrawalOtp::query()
                ->where('user_id', $user->id)
                ->whereNull('consumed_at')
                ->whereNull('invalidated_at')
                ->where('expires_at', '>', now())
                ->latest('id')
                ->first(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['amount' => ['required', 'string', 'max:20']]);

        app(IssueWithdrawalOtp::class)->execute($request->user(), $data['amount']);

        return redirect()->route('wallet.withdrawals')
            ->with('success', 'We emailed you a withdrawal confirmation code. Enter it below to submit your request.');
    }

    public function confirm(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'regex:/^\d{6}$/'],
        ]);

        $user = $request->user();
        $withdrawal = app(ConfirmWithdrawalOtp::class)->execute($user, $data['code']);

        // The confirmation action returns only after its database transaction commits.
        // Email failure must not undo the withdrawal or encourage duplicate submissions.
        try {
            Mail::to($user->email)->send(new WithdrawalRequestReceived(
                name: $user->name,
                amount: (string) $withdrawal->amount,
                walletUrl: route('wallet.index'),
            ));
        } catch (Throwable $exception) {
            Log::error('Withdrawal request confirmation email failed.', [
                'withdrawal_id' => $withdrawal->id,
                'user_id' => $user->id,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);
        }

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
