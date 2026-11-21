<?php

namespace App\Actions\Stores;

use App\Models\Store;
use App\Models\StoreDailyCashRecord;
use App\Models\StoreRemittance;
use App\Models\StoreStaff;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SubmitStoreRemittance
{
    public function execute(User $actor, Store $store, array $data): StoreRemittance
    {
        $this->authorize($actor, $store);

        $data = $this->validated($this->normalize($data));

        return DB::transaction(function () use ($actor, $store, $data): StoreRemittance {
            $record = StoreDailyCashRecord::query()
                ->where('id', $data['store_daily_cash_record_id'])
                ->where('store_id', $store->id)
                ->lockForUpdate()
                ->first();

            if (! $record) {
                throw ValidationException::withMessages([
                    'store_daily_cash_record_id' => 'That business day does not belong to this store.',
                ]);
            }

            if ($record->closed_by === null || $record->closing_cash === null || $record->closing_bank === null) {
                throw ValidationException::withMessages([
                    'store_daily_cash_record_id' => 'Close the business day before remitting its balance.',
                ]);
            }

            if ($data['resubmits_id'] !== null) {
                $this->assertResubmittable($store, $record, (int) $data['resubmits_id']);
            }

            $this->assertWithinClosingBalance($record, $data);

            return StoreRemittance::create([
                'store_id' => $store->id,
                'store_daily_cash_record_id' => $record->id,
                'business_date' => $record->business_date,
                'amount_cash' => $data['amount_cash'],
                'amount_bank' => $data['amount_bank'],
                'method' => $data['method'],
                'reference' => $data['reference'],
                'note' => $data['note'],
                'status' => StoreRemittance::STATUS_PENDING,
                'resubmits_id' => $data['resubmits_id'],
                'submitted_by' => $actor->id,
                'submitted_at' => now(),
            ]);
        });
    }

    private function authorize(User $actor, Store $store): void
    {
        $allowed = $actor->is_active
            && $store->is_active
            && StoreStaff::query()
                ->active()
                ->where('user_id', $actor->id)
                ->where('store_id', $store->id)
                ->whereIn('role', [StoreStaff::ROLE_COORDINATOR, StoreStaff::ROLE_ASSISTANT])
                ->exists();

        if (! $allowed) {
            throw new AuthorizationException('You are not authorized to remit funds for this store.');
        }
    }

    private function normalize(array $data): array
    {
        foreach (['amount_cash', 'amount_bank'] as $key) {
            if (! isset($data[$key]) || $data[$key] === '') {
                $data[$key] = '0';
            }
        }

        foreach (['reference', 'note'] as $key) {
            $value = isset($data[$key]) && is_string($data[$key]) ? trim($data[$key]) : null;
            $data[$key] = $value === '' ? null : $value;
        }

        $data['resubmits_id'] = $data['resubmits_id'] ?? null;

        return $data;
    }

    private function validated(array $data): array
    {
        $validated = Validator::make($data, [
            'store_daily_cash_record_id' => ['required', 'integer'],
            'amount_cash' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
            'amount_bank' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
            'method' => ['required', Rule::in(StoreRemittance::METHODS)],
            'reference' => ['nullable', 'string', 'max:100'],
            'note' => ['nullable', 'string', 'max:1000'],
            'resubmits_id' => ['nullable', 'integer'],
        ])->validate();

        $validated['amount_cash'] = number_format((float) $validated['amount_cash'], 2, '.', '');
        $validated['amount_bank'] = number_format((float) $validated['amount_bank'], 2, '.', '');
        $validated['reference'] = $data['reference'];
        $validated['note'] = $data['note'];
        $validated['resubmits_id'] = $data['resubmits_id'];

        if (bccomp(bcadd($validated['amount_cash'], $validated['amount_bank'], 2), '0', 2) <= 0) {
            throw ValidationException::withMessages([
                'amount_cash' => 'Enter an amount to remit.',
            ]);
        }

        return $validated;
    }

    private function assertResubmittable(Store $store, StoreDailyCashRecord $record, int $originalId): void
    {
        $original = StoreRemittance::query()
            ->where('id', $originalId)
            ->where('store_id', $store->id)
            ->where('store_daily_cash_record_id', $record->id)
            ->where('status', StoreRemittance::STATUS_REJECTED)
            ->lockForUpdate()
            ->first();

        if (! $original) {
            throw ValidationException::withMessages([
                'resubmits_id' => 'Only a rejected remission for this business day can be resubmitted.',
            ]);
        }

        if (StoreRemittance::query()->where('resubmits_id', $original->id)->exists()) {
            throw ValidationException::withMessages([
                'resubmits_id' => 'This rejected remission has already been resubmitted.',
            ]);
        }
    }

    private function assertWithinClosingBalance(StoreDailyCashRecord $record, array $data): void
    {
        $closing = bcadd((string) $record->closing_cash, (string) $record->closing_bank, 2);

        $committed = '0.00';

        StoreRemittance::query()
            ->where('store_daily_cash_record_id', $record->id)
            ->whereIn('status', [StoreRemittance::STATUS_PENDING, StoreRemittance::STATUS_CONFIRMED])
            ->get(['amount_cash', 'amount_bank'])
            ->each(function (StoreRemittance $row) use (&$committed): void {
                $committed = bcadd($committed, $row->totalAmount(), 2);
            });

        $incoming = bcadd($data['amount_cash'], $data['amount_bank'], 2);

        if (bccomp(bcadd($committed, $incoming, 2), $closing, 2) > 0) {
            $remaining = bcsub($closing, $committed, 2);

            throw ValidationException::withMessages([
                'amount_cash' => 'This exceeds the closing balance for the day. Remaining to remit: ₦'
                    .number_format((float) $remaining, 2).'.',
            ]);
        }
    }
}
