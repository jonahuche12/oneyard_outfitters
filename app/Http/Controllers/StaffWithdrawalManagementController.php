<?php

namespace App\Http\Controllers;

use App\Actions\Earnings\ManageStaffWithdrawal;
use App\Mail\WithdrawalStatusUpdated;
use App\Models\StaffWithdrawal;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Throwable;

class StaffWithdrawalManagementController extends Controller
{
    private const STATUSES = [
        'requested',
        'approved',
        'rejected',
        'paid',
        'cancelled',
    ];

    public function index(Request $request): Response
    {
        $this->authorizeManager($request);

        $data = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:requested,approved,rejected,paid,cancelled'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);

        $search = trim($data['search'] ?? '');

        $withdrawals = StaffWithdrawal::query()
            ->with('user')
            ->when(
                $data['status'] ?? null,
                fn (Builder $query, string $status) => $query->where('status', $status)
            )
            ->when(
                $search !== '',
                function (Builder $query) use ($search): void {
                    $query->where(function (Builder $nested) use ($search): void {
                        $nested->whereHas('user', function (Builder $userQuery) use ($search): void {
                            $userQuery->where('name', 'like', '%'.$search.'%')
                                ->orWhere('email', 'like', '%'.$search.'%');
                        })
                            ->orWhere('bank_name', 'like', '%'.$search.'%')
                            ->orWhere('account_name', 'like', '%'.$search.'%')
                            ->orWhere('payment_reference', 'like', '%'.$search.'%');

                        $reference = preg_replace('/^(WD-)/i', '', $search);

                        if ($reference !== '' && ctype_digit($reference)) {
                            $nested->orWhere('id', (int) $reference);
                        }
                    });
                }
            )
            ->when(
                $data['from'] ?? null,
                fn (Builder $query, string $date) => $query->whereDate('requested_at', '>=', $date)
            )
            ->when(
                $data['to'] ?? null,
                fn (Builder $query, string $date) => $query->whereDate('requested_at', '<=', $date)
            )
            ->latest('requested_at')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $summary = [];

        foreach (self::STATUSES as $status) {
            $summary[$status] = StaffWithdrawal::query()
                ->where('status', $status)
                ->count();
        }

        $summary['paid_amount'] = StaffWithdrawal::query()
            ->where('status', 'paid')
            ->sum('amount');

        if ($request->ajax()) {
            return response()
                ->view('earnings.withdrawals.partials.table', ['withdrawals' => $withdrawals])
                ->header('Vary', 'X-Requested-With', false);
        }

        return response()
            ->view('earnings.withdrawals.index', [
            'withdrawals' => $withdrawals,
            'statusFilter' => $data['status'] ?? '',
            'search' => $data['search'] ?? '',
            'fromFilter' => $data['from'] ?? '',
            'toFilter' => $data['to'] ?? '',
            'summary' => $summary,
        ])
            ->header('Vary', 'X-Requested-With', false);
    }

    public function approve(
        Request $request,
        StaffWithdrawal $withdrawal,
        ManageStaffWithdrawal $manager
    ): RedirectResponse {
        $this->authorizeManager($request);

        $data = $request->validate([
            'approval_reason' => ['required', 'string', 'min:10', 'max:1000'],
        ]);

        $updated = $manager->approve(
            $withdrawal,
            $request->user(),
            $data['approval_reason']
        );

        $this->notifyStaff($updated);

        return back()->with('success', 'Withdrawal approved. It is awaiting payment.');
    }

    public function reject(
        Request $request,
        StaffWithdrawal $withdrawal,
        ManageStaffWithdrawal $manager
    ): RedirectResponse {
        $this->authorizeManager($request);

        $data = $request->validate([
            'reject_reason' => ['required', 'string', 'min:10', 'max:1000'],
        ]);

        $updated = $manager->reject(
            $withdrawal,
            $request->user(),
            $data['reject_reason']
        );

        $this->notifyStaff($updated);

        return back()->with('success', 'Withdrawal rejected.');
    }

    public function markPaid(
        Request $request,
        StaffWithdrawal $withdrawal,
        ManageStaffWithdrawal $manager
    ): RedirectResponse {
        $this->authorizeManager($request);

        $data = $request->validate([
            'payment_reference' => ['required', 'string', 'min:3', 'max:120'],
        ]);

        $updated = $manager->markPaid(
            $withdrawal,
            $request->user(),
            $data['payment_reference']
        );

        $this->notifyStaff($updated);

        return back()->with('success', 'Payment recorded and wallet ledger updated.');
    }

    private function authorizeManager(Request $request): void
    {
        abort_unless(
            $request->user()
                && $request->user()->is_active
                && $request->user()->hasPermission(ManageStaffWithdrawal::PERMISSION),
            403
        );
    }

    private function notifyStaff(StaffWithdrawal $withdrawal): void
    {
        try {
            Mail::to($withdrawal->user->email)->send(
                new WithdrawalStatusUpdated(
                    $withdrawal,
                    route('wallet.withdrawals')
                )
            );
        } catch (Throwable $exception) {
            Log::error('Withdrawal status email failed.', [
                'withdrawal_id' => $withdrawal->id,
                'user_id' => $withdrawal->user_id,
                'status' => $withdrawal->status,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
