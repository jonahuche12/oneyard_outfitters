<?php

namespace App\Actions\Earnings;

use App\Models\EarningsActivityWeight;
use App\Models\EarningsWeightAudit;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class UpdateEarningsActivityWeight
{
    /**
     * Create or update an activity weight and retain its audit history.
     *
     * Expected attributes:
     * activity_key, label, points, is_enabled, reason
     */
    public function execute(User $actor, array $attributes): EarningsActivityWeight
    {
        if (! $actor->is_active ||
            ! $actor->hasPermission('earnings.weights.manage')) {
            throw new AuthorizationException(
                'You are not authorized to manage earnings activity weights.'
            );
        }

        $data = Validator::make($attributes, [
            'activity_key' => [
                'required',
                'string',
                'max:100',
                'regex:/\A[a-z][a-z0-9_.-]*\z/',
            ],
            'label' => ['required', 'string', 'max:255'],
            'points' => [
                'required',
                'numeric',
                'min:0',
                'max:999999.9999',
                'regex:/\A\d{1,6}(?:\.\d{1,4})?\z/',
            ],
            'is_enabled' => ['required', 'boolean'],
            'reason' => ['required', 'string', 'min:10', 'max:2000'],
        ])->validate();

        $data['activity_key'] = trim($data['activity_key']);
        $data['label'] = trim($data['label']);
        $data['reason'] = trim($data['reason']);
        $data['points'] = number_format((float) $data['points'], 4, '.', '');
        $data['is_enabled'] = (bool) $data['is_enabled'];

        if ($data['label'] === '' || $data['reason'] === '') {
            throw ValidationException::withMessages([
                'reason' => 'A meaningful label and audit reason are required.',
            ]);
        }

        return DB::transaction(function () use ($actor, $data) {
            $weight = EarningsActivityWeight::query()
                ->where('activity_key', $data['activity_key'])
                ->lockForUpdate()
                ->first();

            $oldPoints = $weight?->points;
            $oldEnabled = $weight?->is_enabled;

            if ($weight) {
                $weight->update([
                    'label' => $data['label'],
                    'points' => $data['points'],
                    'is_enabled' => $data['is_enabled'],
                    'updated_by' => $actor->id,
                ]);
            } else {
                $weight = EarningsActivityWeight::query()->create([
                    'activity_key' => $data['activity_key'],
                    'label' => $data['label'],
                    'points' => $data['points'],
                    'is_enabled' => $data['is_enabled'],
                    'updated_by' => $actor->id,
                ]);
            }

            EarningsWeightAudit::query()->create([
                'activity_weight_id' => $weight->id,
                'activity_key' => $weight->activity_key,
                'old_points' => $oldPoints,
                'new_points' => $data['points'],
                'old_enabled' => $oldEnabled,
                'new_enabled' => $data['is_enabled'],
                'changed_by' => $actor->id,
                'reason' => $data['reason'],
                'metadata' => [
                    'label' => $data['label'],
                    'operation' => $oldPoints === null ? 'created' : 'updated',
                ],
                'created_at' => now(),
            ]);

            return $weight->refresh();
        });
    }
}
