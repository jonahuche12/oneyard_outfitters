<?php

namespace App\Http\Controllers;

use App\Actions\Earnings\AdjustStaffWallet;
use App\Models\StaffWalletEntry;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class StaffWalletAdminController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', StaffWalletEntry::class);

        $search = trim((string) $request->query('q', ''));

        $totals = StaffWalletEntry::query()
            ->selectRaw('user_id, SUM(amount) as balance, MAX(created_at) as last_at')
            ->groupBy('user_id')
            ->get()
            ->keyBy('user_id');

        $users = User::query()
            ->where(fn ($q) => $q->where('is_active', true)->orWhereIn('id', $totals->keys()))
            ->when($search !== '', fn ($q) => $q->where(
                fn ($w) => $w->where('name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%')
            ))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('earnings.wallets.index', compact('users', 'totals', 'search'));
    }

    public function show(User $user): View
    {
        Gate::authorize('viewAny', StaffWalletEntry::class);

        $entries = StaffWalletEntry::query()
            ->where('user_id', $user->id)
            ->with('pool.order')
            ->latest('id')
            ->paginate(25);

        $reversedIds = StaffWalletEntry::query()
            ->whereIn('reverses_entry_id', $entries->pluck('id'))
            ->pluck('reverses_entry_id')
            ->all();

        $balance = StaffWalletEntry::balanceFor($user);

        return view('earnings.wallets.show', compact('user', 'entries', 'reversedIds', 'balance'));
    }

    public function adjust(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('adjust', StaffWalletEntry::class);

        $data = $request->validate([
            'amount' => ['required', 'string', 'max:20'],
            'adjust_reason' => ['required', 'string', 'max:1000'],
        ]);

        try {
            app(AdjustStaffWallet::class)->adjust($user, $data['amount'], $data['adjust_reason'], $request->user());
        } catch (ValidationException $e) {
            $errors = $e->errors();
            if (isset($errors['reason'])) {
                $errors['adjust_reason'] = $errors['reason'];
                unset($errors['reason']);
            }

            throw ValidationException::withMessages($errors);
        }

        return redirect()->route('earnings.wallets.show', $user)
            ->with('success', 'The adjustment has been recorded.');
    }

    public function reverse(Request $request, User $user, StaffWalletEntry $entry): RedirectResponse
    {
        Gate::authorize('adjust', StaffWalletEntry::class);
        abort_unless($entry->user_id === $user->id, 404);

        $data = $request->validate([
            'reverse_reason' => ['required', 'string', 'max:1000'],
        ]);

        try {
            app(AdjustStaffWallet::class)->reverse($entry, $data['reverse_reason'], $request->user());
        } catch (ValidationException $e) {
            $errors = $e->errors();
            if (isset($errors['reason'])) {
                $errors['reverse_reason'] = $errors['reason'];
                unset($errors['reason']);
            }

            throw ValidationException::withMessages($errors);
        }

        return redirect()->route('earnings.wallets.show', $user)
            ->with('success', 'The entry has been reversed.');
    }
}
