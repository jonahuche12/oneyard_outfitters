<?php

namespace App\Actions\Stock;

use App\Models\StockItem;
use App\Models\StockItemPhoto;
use App\Models\StockLevel;
use App\Models\StockMovement;
use App\Models\Store;
use App\Models\StoreStaff;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class CreateStockItem
{
    public function execute(User $actor, Store $store, array $data): StockItem
    {
        $storedPaths = [];

        try {
            return DB::transaction(function () use ($actor, $store, $data, &$storedPaths): StockItem {
                $lockedActor = User::query()->lockForUpdate()->findOrFail($actor->id);

                if (! $lockedActor->is_active) {
                    throw new AuthorizationException('Inactive users cannot register stock.');
                }

                $lockedStore = Store::query()->lockForUpdate()->findOrFail($store->id);

                if (! $lockedStore->is_active) {
                    throw new AuthorizationException('Stock cannot be registered for an inactive store.');
                }

                $isCoordinator = StoreStaff::query()
                    ->where('user_id', $lockedActor->id)
                    ->where('store_id', $lockedStore->id)
                    ->where('role', StoreStaff::ROLE_COORDINATOR)
                    ->whereNull('ends_at')
                    ->exists();

                if (! $isCoordinator) {
                    throw new AuthorizationException(
                        'Only the assigned store coordinator can register stock items.'
                    );
                }

                $validated = Validator::make($data, [
                    'description' => ['required', 'string', 'max:200'],
                    'category' => ['nullable', 'string', 'max:80'],
                    'grade' => ['nullable', 'string', 'max:50'],
                    'unit' => ['required', 'string', 'in:'.implode(',', StockItem::UNITS)],
                    'is_raw' => ['sometimes', 'boolean'],
                    'quantity' => ['required', 'numeric', 'gt:0', 'decimal:0,3'],
                    'unit_cost' => ['required', 'numeric', 'min:0', 'decimal:0,6'],
                    'suggested_price' => ['nullable', 'numeric', 'min:0'],
                    'current_market_price' => ['nullable', 'numeric', 'min:0'],
                    'photos' => ['sometimes', 'array', 'max:10'],
                    'photos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
                ])->validate();

                $quantity = (string) $validated['quantity'];
                $unitCost = (string) $validated['unit_cost'];
                $openingValue = round((float) $quantity * (float) $unitCost, 2);

                $item = StockItem::query()->create([
                    'store_id' => $lockedStore->id,
                    'intake_id' => null,
                    'description' => trim($validated['description']),
                    'category' => isset($validated['category']) ? trim($validated['category']) : null,
                    'grade' => isset($validated['grade']) ? trim($validated['grade']) : null,
                    'unit' => $validated['unit'],
                    'is_raw' => $validated['is_raw'] ?? false,
                    'unit_cost' => $unitCost,
                    'suggested_price' => $validated['suggested_price'] ?? null,
                    'current_market_price' => $validated['current_market_price'] ?? null,
                    // Retained only for compatibility with historical records.
                    // This field no longer controls stock availability.
                    'approval_status' => StockItem::APPROVAL_PENDING,
                    'approved_by' => null,
                    'approved_at' => null,
                    'rejection_reason' => null,
                    'created_by' => $lockedActor->id,
                ]);

                StockLevel::query()->create([
                    'stock_item_id' => $item->id,
                    'store_id' => $lockedStore->id,
                    'quantity' => $quantity,
                    'quantity_quarantined' => 0,
                    'average_unit_cost' => $unitCost,
                    'inventory_value' => $openingValue,
                    'quarantine_value' => 0,
                ]);

                StockMovement::query()->create([
                    'stock_item_id' => $item->id,
                    'store_id' => $lockedStore->id,
                    'type' => StockMovement::TYPE_OPENING_STOCK,
                    'quantity' => $quantity,
                    'unit_cost' => $unitCost,
                    'movement_value' => $openingValue,
                    'source_type' => StockItem::class,
                    'source_id' => $item->id,
                    'reason' => 'Opening stock registration',
                    'created_by' => $lockedActor->id,
                ]);

                foreach (($validated['photos'] ?? []) as $photo) {
                    $extension = strtolower($photo->getClientOriginalExtension());
                    $filename = Str::uuid()->toString().'.'.$extension;
                    $path = $photo->storeAs('stock-items/'.$item->id, $filename, 'local');

                    if ($path === false) {
                        throw new RuntimeException('A stock photo could not be stored.');
                    }

                    $storedPaths[] = $path;

                    StockItemPhoto::query()->create([
                        'stock_item_id' => $item->id,
                        'file_path' => $path,
                        'original_name' => $photo->getClientOriginalName(),
                        'mime_type' => $photo->getMimeType() ?: 'application/octet-stream',
                        'file_size' => $photo->getSize(),
                        'uploaded_by' => $lockedActor->id,
                    ]);
                }

                return $item->load(['levels', 'photos']);
            });
        } catch (Throwable $exception) {
            foreach ($storedPaths as $path) {
                Storage::disk('local')->delete($path);
            }

            throw $exception;
        }
    }
}
