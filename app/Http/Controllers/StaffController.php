<?php

namespace App\Http\Controllers;

use App\Http\Requests\Staff\StoreStaffRequest;
use App\Http\Requests\Staff\UpdateStaffRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
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
    
        $staff = DB::transaction(function () use ($validated) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
                'is_active' => true,
            ]);
    
            if (
                auth()->user()->hasPermission('users.assign-roles')
                && ! empty($validated['roles'])
            ) {
                $user->roles()->sync($validated['roles']);
            }
    
            return $user;
        });
    
        return redirect()
            ->route('staff.show', $staff)
            ->with('success', 'Staff account created successfully.');
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