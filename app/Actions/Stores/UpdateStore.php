<?php

namespace App\Actions\Stores;

use App\Models\Store;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class UpdateStore
{
    use StoreActionSupport;

    /**
     * Updates name, code, address and phone. The store type is fixed at creation.
     */
    public function execute(User $actor, Store $store, array $input): Store
    {
        $this->authorizeStoreManager($actor);

        $validated = Validator::make(
            $this->normalizeStoreInput($input),
            $this->storeFieldRules($store->getKey())
        )->validate();

        try {
            return DB::transaction(function () use ($store, $validated): Store {
                $locked = Store::query()->lockForUpdate()->findOrFail($store->getKey());

                $locked->fill(Arr::only($validated, ['name', 'code', 'address', 'phone']))->save();

                return $locked;
            });
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages([
                'name' => 'This conflicts with another change made at the same time. Refresh and try again.',
            ]);
        }
    }
}
