<?php

namespace App\Actions\Stores;

use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SetStoreActive
{
    use StoreActionSupport;

    public function execute(User $actor, Store $store, bool $active): Store
    {
        $this->authorizeStoreManager($actor);

        return DB::transaction(function () use ($store, $active): Store {
            $locked = Store::query()->lockForUpdate()->findOrFail($store->getKey());

            if ($locked->is_active === $active) {
                throw ValidationException::withMessages([
                    'is_active' => $active
                        ? 'This store is already active.'
                        : 'This store is already inactive.',
                ]);
            }

            if (! $active && $locked->isWarehouse()) {
                throw ValidationException::withMessages([
                    'is_active' => 'The general warehouse cannot be deactivated.',
                ]);
            }

            $locked->forceFill(['is_active' => $active])->save();

            return $locked;
        });
    }
}
