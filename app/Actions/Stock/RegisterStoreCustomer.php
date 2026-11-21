<?php

namespace App\Actions\Stock;

use App\Models\Customer;
use App\Models\Store;
use App\Models\StoreStaff;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RegisterStoreCustomer
{
    public function execute(User $actor, Store $store, array $data): Customer
    {
        return DB::transaction(function () use ($actor, $store, $data): Customer {
            $actor = User::query()->lockForUpdate()->findOrFail($actor->id);
            $store = Store::query()->lockForUpdate()->findOrFail($store->id);

            if (! $actor->is_active || ! $store->is_active) {
                throw new AuthorizationException('An active user and store are required.');
            }

            $assigned = StoreStaff::query()
                ->active()
                ->where('user_id', $actor->id)
                ->where('store_id', $store->id)
                ->whereIn('role', [
                    StoreStaff::ROLE_COORDINATOR,
                    StoreStaff::ROLE_ASSISTANT,
                ])
                ->exists();

            if (! $assigned) {
                throw new AuthorizationException(
                    'Only the assigned store coordinator or assistant can manage customers.'
                );
            }

            $validated = Validator::make($data, [
                'name' => ['required', 'string', 'max:200'],
                'phone' => ['required', 'string', 'max:50'],
                'alternate_phone' => ['nullable', 'string', 'max:50'],
                'email' => ['nullable', 'email', 'max:255'],
                'address' => ['nullable', 'string', 'max:10000'],
                'notes' => ['nullable', 'string', 'max:10000'],
            ])->validate();

            $code = null;

            for ($attempt = 0; $attempt < 5; $attempt++) {
                $candidate = 'CUS-'.Str::upper(Str::random(10));

                if (! Customer::withTrashed()
                    ->where('store_id', $store->id)
                    ->where('customer_code', $candidate)
                    ->exists()) {
                    $code = $candidate;
                    break;
                }
            }

            if ($code === null) {
                throw ValidationException::withMessages([
                    'name' => 'A customer reference could not be generated. Please try again.',
                ]);
            }

            return Customer::query()->create([
                'store_id' => $store->id,
                'customer_code' => $code,
                'name' => $validated['name'],
                'phone' => $validated['phone'],
                'alternate_phone' => $validated['alternate_phone'] ?? null,
                'email' => $validated['email'] ?? null,
                'address' => $validated['address'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'is_active' => true,
            ]);
        });
    }
}
