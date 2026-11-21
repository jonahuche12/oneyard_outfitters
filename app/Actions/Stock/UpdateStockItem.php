<?php

namespace App\Actions\Stock;

use App\Models\StockItem;
use App\Models\StockItemPhoto;
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

class UpdateStockItem
{
    public function execute(User $actor, StockItem $item, array $data): StockItem
    {
        $storedPaths = [];

        try {
            return DB::transaction(function () use ($actor, $item, $data, &$storedPaths): StockItem {
                $lockedActor = User::query()->lockForUpdate()->findOrFail($actor->id);
                $lockedItem = StockItem::query()->lockForUpdate()->findOrFail($item->id);
                $lockedStore = Store::query()->lockForUpdate()->findOrFail($lockedItem->store_id);

                if (! $lockedActor->is_active || ! $lockedStore->is_active) {
                    throw new AuthorizationException('Only active coordinators can edit stock items in an active store.');
                }

                $isCoordinator = StoreStaff::query()
                    ->where('user_id', $lockedActor->id)
                    ->where('store_id', $lockedStore->id)
                    ->where('role', StoreStaff::ROLE_COORDINATOR)
                    ->whereNull('ends_at')
                    ->exists();

                if (! $isCoordinator) {
                    throw new AuthorizationException('Only the assigned store coordinator can edit stock items.');
                }

                $existingPhotoCount = StockItemPhoto::query()
                    ->where('stock_item_id', $lockedItem->id)
                    ->lockForUpdate()
                    ->count();

                $remainingPhotoSlots = max(0, 10 - $existingPhotoCount);

                $validated = Validator::make($data, [
                    'description' => ['required', 'string', 'max:200'],
                    'category' => ['nullable', 'string', 'max:80'],
                    'grade' => ['nullable', 'string', 'max:50'],
                    'suggested_price' => ['nullable', 'numeric', 'min:0'],
                    'current_market_price' => ['nullable', 'numeric', 'min:0'],
                    'photos' => ['sometimes', 'array', 'max:'.$remainingPhotoSlots],
                    'photos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
                ])->validate();

                $lockedItem->forceFill([
                    'description' => trim($validated['description']),
                    'category' => isset($validated['category']) ? trim($validated['category']) : null,
                    'grade' => isset($validated['grade']) ? trim($validated['grade']) : null,
                    'suggested_price' => $validated['suggested_price'] ?? null,
                    'current_market_price' => $validated['current_market_price'] ?? null,
                ])->save();

                foreach (($validated['photos'] ?? []) as $photo) {
                    $extension = strtolower($photo->getClientOriginalExtension());
                    $filename = Str::uuid()->toString().'.'.$extension;
                    $path = $photo->storeAs('stock-items/'.$lockedItem->id, $filename, 'local');

                    if ($path === false) {
                        throw new RuntimeException('A stock photo could not be stored.');
                    }

                    $storedPaths[] = $path;

                    StockItemPhoto::query()->create([
                        'stock_item_id' => $lockedItem->id,
                        'file_path' => $path,
                        'original_name' => $photo->getClientOriginalName(),
                        'mime_type' => $photo->getMimeType() ?: 'application/octet-stream',
                        'file_size' => $photo->getSize(),
                        'uploaded_by' => $lockedActor->id,
                    ]);
                }

                return $lockedItem->load(['store', 'levels', 'photos']);
            });
        } catch (Throwable $exception) {
            foreach ($storedPaths as $path) {
                Storage::disk('local')->delete($path);
            }

            throw $exception;
        }
    }
}
