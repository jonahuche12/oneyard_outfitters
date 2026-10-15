<?php

namespace App\Http\Controllers;

use App\Models\StaffWalletEntry;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WalletController extends Controller
{
    /**
     * Read-only wallet for the logged-in staff member. The user is always the
     * authenticated user; no user id is accepted from the request.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        $entries = StaffWalletEntry::query()
            ->where('user_id', $user->id)
            ->with('pool.order')
            ->latest('id')
            ->paginate(20);

        $balance = StaffWalletEntry::balanceFor($user);

        $available = \App\Models\StaffWithdrawal::available($user->id);

        return view('wallet.index', compact('entries', 'balance', 'available'));
    }
}
