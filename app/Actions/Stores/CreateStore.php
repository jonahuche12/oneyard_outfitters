<?php

namespace App\Actions\Stores;

use App\Models\Store;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CreateStore
{
    use StoreActionSupport;

    public function execute(User $actor, array $input): Store
    {
        $this->authorizeStoreManager($actor);

        $validated = Validator::make(
            $this->normalizeStoreInput($input),
            $this->storeFieldRules() + [
                'type' => ['required', Rule::in(Store::TYPES)],
            ]
        )->validate();

        try {
            return DB::transaction(function () use ($actor, $validated): Store {
                if (
                    $validated['type'] === Store::TYPE_WAREHOUSE
                    && Store::query()->where('type', Store::TYPE_WAREHOUSE)->exists()
                ) {
                    throw ValidationException::withMessages([
                        'type' => 'A general warehouse already exists.',
                    ]);
                }

                return Store::query()->create([
                    'name' => $validated['name'],
                    'code' => $validated['code'],
                    'type' => $validated['type'],
                    'address' => $validated['address'] ?? null,
                    'phone' => $validated['phone'] ?? null,
                    'is_active' => true,
                    'created_by' => $actor->id,
                ]);
            });
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages([
                'name' => 'This conflicts with another change made at the same time (name, code or warehouse). Refresh and try again.',
            ]);
        }
    }
}
