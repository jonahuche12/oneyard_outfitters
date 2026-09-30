<?php

namespace App\Http\Requests\Procurement;

use App\Models\Procurement;
use App\Models\ProcurementOffer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreProcurementOfferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'quantity' => [
                'required',
                'numeric',
                'gt:0',
                'lte:' . (float) $this->route('procurement')->quantity,
            ],
            'unit_price' => [
                'required',
                'numeric',
                'min:0',
                'lte:' . (float) $this->route('procurement')->maximum_unit_price,
            ],
            'notes' => [
                'nullable',
                'string',
                'max:5000',
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            /** @var Procurement $procurement */
            $procurement = $this->route('procurement');

            if ($procurement->status !== Procurement::STATUS_READY) {
                $validator->errors()->add(
                    'procurement',
                    'This procurement is not currently accepting offers.'
                );

                return;
            }

            $user = $this->user();

            if ($user !== null) {
                $offerCount = ProcurementOffer::query()
                    ->where('procurement_id', $procurement->id)
                    ->where('user_id', $user->id)
                    ->count();

                if ($offerCount >= 3) {
                    $validator->errors()->add(
                        'offer',
                        'You have already submitted the maximum of 3 offers for this procurement.'
                    );

                    return;
                }
            }

            if (
                $procurement->offer_deadline !== null
                && $procurement->offer_deadline->isPast()
            ) {
                $validator->errors()->add(
                    'procurement',
                    'The offer deadline for this procurement has passed.'
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            'quantity.lte' =>
                'The quantity available cannot exceed the procurement quantity.',

            'unit_price.lte' =>
                'The unit price cannot exceed the maximum allowed unit price.',
        ];
    }
}
