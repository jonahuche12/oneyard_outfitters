<?php

namespace App\Http\Requests\Procurement;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProcurementOfferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $offer = $this->route('offer');

        return [
            'quantity' => [
                'required',
                'numeric',
                'gt:0',
                'lte:' . (float) $offer->procurement->quantity,
            ],
            'unit_price' => [
                'required',
                'numeric',
                'min:0',
                'lte:' . (float) $offer->procurement->maximum_unit_price,
            ],
            'notes' => [
                'nullable',
                'string',
                'max:5000',
            ],
        ];
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
