<?php

namespace App\Actions\Stock;

use App\Models\StockItem;
use App\Models\StockItemApproval;
use App\Models\StoreStaff;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ReviewStockItem
{
    public function execute(User $actor, StockItem $item, array $data): StockItem
    {
        return DB::transaction(function () use ($actor, $item, $data): StockItem {
            $lockedActor = User::query()->lockForUpdate()->findOrFail($actor->id);

            if (! $lockedActor->is_active) {
                throw new AuthorizationException('Inactive users cannot review stock items.');
            }

            $lockedItem = StockItem::query()->lockForUpdate()->findOrFail($item->id);

            $isOwningStoreCoordinator = StoreStaff::query()
                ->where('user_id', $lockedActor->id)
                ->where('store_id', $lockedItem->store_id)
                ->where('role', StoreStaff::ROLE_COORDINATOR)
                ->whereNull('ends_at')
                ->exists();

            if (! $isOwningStoreCoordinator) {
                throw new AuthorizationException(
                    'Only the owning store coordinator can review stock items.'
                );
            }

            if ($lockedItem->approval_status !== StockItem::APPROVAL_PENDING) {
                throw ValidationException::withMessages([
                    'item' => 'Only pending stock items can be reviewed.',
                ]);
            }

            $validated = Validator::make($data, [
                'decision' => ['required', Rule::in([
                    StockItemApproval::DECISION_APPROVED,
                    StockItemApproval::DECISION_REJECTED,
                ])],
                'reason' => [
                    Rule::requiredIf(($data['decision'] ?? null) === StockItemApproval::DECISION_REJECTED),
                    'nullable',
                    'string',
                    'max:5000',
                ],
            ])->validate();

            $approved = $validated['decision'] === StockItemApproval::DECISION_APPROVED;
            $reviewedAt = now();

            $lockedItem->forceFill([
                'approval_status' => $validated['decision'],
                'approved_by' => $approved ? $lockedActor->id : null,
                'approved_at' => $approved ? $reviewedAt : null,
                'rejection_reason' => $approved ? null : trim($validated['reason']),
            ])->save();

            $lockedItem->approvals()->create([
                'decision' => $validated['decision'],
                'reason' => $validated['reason'] ?? null,
                'reviewed_by' => $lockedActor->id,
                'reviewed_at' => $reviewedAt,
            ]);

            return $lockedItem->refresh();
        });
    }
}
