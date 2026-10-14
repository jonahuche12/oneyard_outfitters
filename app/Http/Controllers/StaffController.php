<?php

namespace App\Http\Controllers;

use App\Http\Requests\Staff\StoreStaffRequest;
use App\Jobs\SendStaffOnboardingWhatsApp;
use App\Mail\StaffOnboardingInvitation;
use App\Models\StaffOnboardingToken;
use App\Http\Requests\Staff\UpdateStaffRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\View\View;

class StaffController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', User::class);

        $search = trim((string) $request->input('search'));

        $staff = User::query()
            ->with('roles')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('staff.index', [
            'staff' => $staff,
            'search' => $search,
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', User::class);

        $roles = Role::query()
            ->orderBy('name')
            ->get();

        return view('staff.create', [
            'roles' => $roles,
        ]);
    }

    public function store(StoreStaffRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        Gate::authorize('create', User::class);

        $rawToken = Str::random(64);

        [$staff, $setupUrl] = DB::transaction(
            function () use ($validated, $rawToken) {
                $user = User::create([
                    'name' => $validated['name'],
                    'email' => $validated['email'],
                    'phone' => $validated['phone'],
                    'password' => Str::random(64),
                    'password_set_at' => null,
                    'email_verified_at' => null,
                    'is_active' => false,
                ]);

                if (
                    auth()->user()->hasPermission('users.assign-roles')
                    && ! empty($validated['roles'])
                ) {
                    $user->roles()->sync($validated['roles']);
                }

                StaffOnboardingToken::create([
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'token_hash' => hash('sha256', $rawToken),
                    'expires_at' => now()->addHours(24),
                ]);

                $setupUrl = route('staff.onboarding.show', [
                    'token' => $rawToken,
                ]);

                return [$user, $setupUrl];
            }
        );

        try {
            Mail::to($staff->email)->queue(
                new StaffOnboardingInvitation(
                    $staff,
                    $setupUrl,
                )
            );
        } catch (\Throwable $exception) {
            Log::error('Could not queue staff onboarding email.', [
                'staff_id' => $staff->id,
                'exception' => $exception->getMessage(),
            ]);
        }

        try {
            SendStaffOnboardingWhatsApp::dispatch(
                $staff->phone,
                $staff->name,
                $setupUrl,
                $staff->id,
            )->afterCommit();
        } catch (\Throwable $exception) {
            Log::error('Could not queue staff onboarding WhatsApp.', [
                'staff_id' => $staff->id,
                'exception' => $exception->getMessage(),
            ]);
        }

        return redirect()
            ->route('staff.show', $staff)
            ->with(
                'success',
                'Staff account created inactive. A password setup link has been sent; the account activates when the password is set.'
            );
    }

    public function show(User $user): View
    {
        Gate::authorize('view', $user);

        $user->load('roles');

        return view('staff.show', [
            'staff' => $user,
        ]);
    }

    public function edit(User $user): View
    {
        Gate::authorize('update', $user);

        $user->load('roles');

        $roles = Role::query()
            ->orderBy('name')
            ->get();

        return view('staff.edit', [
            'staff' => $user,
            'roles' => $roles,
        ]);
    }

    public function update(
        UpdateStaffRequest $request,
        User $user
    ): RedirectResponse {
        Gate::authorize('update', $user);

        $validated = $request->validated();

        DB::transaction(function () use ($validated, $user) {
            $user->update([
                'name' => $validated['name'],
                'email' => $validated['email'],
                ...(array_key_exists('phone', $validated)
                    ? ['phone' => $validated['phone']]
                    : []),
            ]);

            if (! empty($validated['password'])) {
                $user->update([
                    'password' => Hash::make($validated['password']),
                ]);
            }

            if (auth()->user()->hasPermission('users.assign-roles')) {
                $user->roles()->sync($validated['roles']);
            }
        });

        return redirect()
            ->route('staff.show', $user)
            ->with('success', 'Staff account updated successfully.');
    }

    public function resendOnboarding(User $user): RedirectResponse
    {
        $actor = auth()->user();

        abort_unless(
            $actor && $actor->roles()
                ->where('slug', 'super-admin')
                ->exists(),
            403
        );

        if ($user->is_active || $user->password_set_at !== null) {
            return back()->withErrors([
                'staff' => 'Password setup is already complete for this account.',
            ]);
        }

        $rawToken = Str::random(64);

        $setupUrl = DB::transaction(function () use ($user, $rawToken) {
            $lockedUser = User::query()
                ->whereKey($user->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedUser->is_active || $lockedUser->password_set_at !== null) {
                return null;
            }

            StaffOnboardingToken::query()
                ->where('user_id', $lockedUser->id)
                ->whereNull('consumed_at')
                ->update(['consumed_at' => now()]);

            StaffOnboardingToken::create([
                'user_id' => $lockedUser->id,
                'email' => $lockedUser->email,
                'token_hash' => hash('sha256', $rawToken),
                'expires_at' => now()->addHours(24),
            ]);

            return route('staff.onboarding.show', [
                'token' => $rawToken,
            ]);
        });

        if ($setupUrl === null) {
            return back()->withErrors([
                'staff' => 'Password setup is already complete for this account.',
            ]);
        }

        try {
            Mail::to($user->email)->queue(
                new StaffOnboardingInvitation($user, $setupUrl)
            );
        } catch (\Throwable $exception) {
            Log::error('Could not queue staff onboarding email.', [
                'staff_id' => $user->id,
                'exception' => $exception->getMessage(),
            ]);
        }

        try {
            SendStaffOnboardingWhatsApp::dispatch(
                $user->phone,
                $user->name,
                $setupUrl,
                $user->id,
            )->afterCommit();
        } catch (\Throwable $exception) {
            Log::error('Could not queue staff onboarding WhatsApp.', [
                'staff_id' => $user->id,
                'exception' => $exception->getMessage(),
            ]);
        }

        return redirect()
            ->route('staff.show', $user)
            ->with(
                'success',
                'A fresh password setup invitation has been queued. Check the queue status if delivery does not arrive.'
            );
    }

    public function activate(User $user): RedirectResponse
    {
        Gate::authorize('activate', $user);

        $user->update([
            'is_active' => true,
        ]);

        return redirect()
            ->route('staff.show', $user)
            ->with('success', 'Staff account activated successfully.');
    }

    public function deactivate(User $user): RedirectResponse
    {
        Gate::authorize('deactivate', $user);

        if ($user->is(auth()->user())) {
            return back()->withErrors([
                'staff' => 'You cannot deactivate your own account.',
            ]);
        }

        $user->update([
            'is_active' => false,
        ]);

        return redirect()
            ->route('staff.show', $user)
            ->with('success', 'Staff account deactivated successfully.');
    }
}