<?php

namespace App\Actions\Stores;

use App\Models\Store;
use App\Models\StoreStaff;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

trait StoreActionSupport
{
    protected function authorizeStoreManager(User $actor): void
    {
        if (! $actor->is_active || ! $actor->hasPermission('stores.manage')) {
            throw new AuthorizationException('You are not authorized to manage stores.');
        }
    }

    /**
     * Trim text, uppercase the code and turn blank optional fields into null.
     */
    protected function normalizeStoreInput(array $input): array
    {
        foreach (['name', 'address', 'phone'] as $key) {
            if (isset($input[$key]) && is_string($input[$key])) {
                $input[$key] = trim($input[$key]);
            }
        }

        if (isset($input['code']) && is_string($input['code'])) {
            $input['code'] = strtoupper(trim($input['code']));
        }

        foreach (['address', 'phone'] as $key) {
            if (array_key_exists($key, $input) && $input[$key] === '') {
                $input[$key] = null;
            }
        }

        return $input;
    }

    protected function storeFieldRules(?int $ignoreStoreId = null): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:120', Rule::unique('stores', 'name')->ignore($ignoreStoreId)],
            'code' => ['required', 'string', 'min:2', 'max:20', 'regex:/^[A-Z0-9-]+$/', Rule::unique('stores', 'code')->ignore($ignoreStoreId)],
            'address' => ['nullable', 'string', 'max:500'],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+()\-\s]+$/'],
        ];
    }

    protected function assertStoreAcceptsStaff(Store $store): void
    {

        if (! $store->is_active) {
            throw ValidationException::withMessages([
                'store' => 'Staff cannot be assigned to an inactive store.',
            ]);
        }
    }

    protected function assertUserAssignable(User $user): void
    {
        if (! $user->is_active) {
            throw ValidationException::withMessages([
                'user_id' => 'Only active users can be assigned to a store.',
            ]);
        }
    }

    /**
     * End the store's active coordinator assignment, if there is one.
     */
    protected function endCurrentCoordinator(Store $store, User $actor, string $reason): void
    {
        $current = StoreStaff::query()
            ->active()
            ->where('store_id', $store->id)
            ->where('role', StoreStaff::ROLE_COORDINATOR)
            ->lockForUpdate()
            ->first();

        if ($current) {
            $current->forceFill([
                'ends_at' => now(),
                'ended_reason' => $reason,
                'ended_by' => $actor->id,
            ])->save();
        }
    }
}
