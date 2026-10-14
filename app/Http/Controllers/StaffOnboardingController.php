<?php

namespace App\Http\Controllers;

use App\Models\StaffOnboardingToken;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;

class StaffOnboardingController extends Controller
{
    public function show(string $token): View
    {
        $onboardingToken = $this->validToken($token);

        abort_unless($onboardingToken, 404);

        return view('auth.staff-onboarding', [
            'token' => $token,
            'email' => $onboardingToken->email,
        ]);
    }

    public function store(Request $request, string $token): RedirectResponse
    {
        $onboardingToken = $this->validToken($token);

        abort_unless($onboardingToken, 404);

        $validated = $request->validate([
            'password' => ['required', 'string', PasswordRule::default(), 'confirmed'],
        ]);

        $completed = DB::transaction(function () use ($token, $validated) {
            $record = StaffOnboardingToken::query()
                ->where('token_hash', hash('sha256', $token))
                ->lockForUpdate()
                ->first();

            if (
                ! $record
                || $record->consumed_at !== null
                || $record->expires_at->isPast()
            ) {
                return false;
            }

            $user = User::query()
                ->whereKey($record->user_id)
                ->lockForUpdate()
                ->first();

            if (
                ! $user
                || $user->is_active
                || $user->password_set_at !== null
                || $user->email !== $record->email
            ) {
                return false;
            }

            $user->password = Hash::make($validated['password']);
            $user->password_set_at = now();
            $user->is_active = true;

            $user->save();

            $record->consumed_at = now();
            $record->save();

            return true;
        });

        if (! $completed) {
            abort(404);
        }

        return redirect()
            ->route('login')
            ->with(
                'status',
                'Password set successfully. Your account is now active. You can sign in.'
            );
    }

    private function validToken(string $token): ?StaffOnboardingToken
    {
        $record = StaffOnboardingToken::query()
            ->with('user')
            ->where('token_hash', hash('sha256', $token))
            ->whereNull('consumed_at')
            ->where('expires_at', '>', now())
            ->first();

        if (
            ! $record
            || ! $record->user
            || $record->user->is_active
            || $record->user->password_set_at !== null
            || $record->user->email !== $record->email
        ) {
            return null;
        }

        return $record;
    }
}
