<?php

namespace App\Http\Controllers;

use App\Actions\Stores\AssignStoreStaff;
use App\Actions\Stores\EndStoreStaffAssignment;
use App\Actions\Stores\ReassignStoreStaff;
use App\Models\Store;
use App\Models\StoreStaff;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class StoreStaffController extends Controller
{
    public function assign(Request $request, Store $store, AssignStoreStaff $action): RedirectResponse
    {
        Gate::authorize('manageStaff', $store);

        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'role' => ['required', 'string'],
        ]);

        $user = User::query()->findOrFail($validated['user_id']);

        $assignment = $action->execute($request->user(), $store, $user, $validated['role']);

        return redirect()
            ->route('stores.show', $store)
            ->with('success', $user->name.' assigned as '.$assignment->role.'.');
    }

    /**
     * Move a user to another store, or change their role inside the same store.
     * {store} is the store the assignment currently belongs to.
     */
    public function move(Request $request, Store $store, StoreStaff $assignment, ReassignStoreStaff $action): RedirectResponse
    {
        Gate::authorize('manageStaff', $store);

        abort_unless($assignment->store_id === $store->id, 404);

        $validated = $request->validate([
            'to_store_id' => ['required', 'integer', 'exists:stores,id'],
            'role' => ['required', 'string'],
        ]);

        // Guards against a stale page: the action moves the user's *current*
        // assignment, so the one named in the URL must still be that one.
        if (! $assignment->isActive()) {
            throw ValidationException::withMessages([
                'assignment' => 'This assignment has already ended. Refresh the page and try again.',
            ]);
        }

        $user = $assignment->user;
        $toStore = Store::query()->findOrFail($validated['to_store_id']);

        $new = $action->execute($request->user(), $user, $toStore, $validated['role']);

        $message = $toStore->is($store)
            ? $user->name.' is now '.$new->role.'.'
            : $user->name.' moved to '.$toStore->name.'.';

        return redirect()
            ->route('stores.show', $store)
            ->with('success', $message);
    }

    public function end(Request $request, Store $store, StoreStaff $assignment, EndStoreStaffAssignment $action): RedirectResponse
    {
        Gate::authorize('manageStaff', $store);

        abort_unless($assignment->store_id === $store->id, 404);

        $validated = $request->validate([
            'reason' => ['required', 'string'],
        ]);

        $action->execute($request->user(), $assignment, $validated['reason']);

        return redirect()
            ->route('stores.show', $store)
            ->with('success', $assignment->user->name."'s assignment ended.");
    }
}
