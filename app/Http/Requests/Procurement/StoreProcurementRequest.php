<?php

namespace App\Http\Requests\Procurement;

use Illuminate\Foundation\Http\FormRequest;

class StoreProcurementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'item_name' => [
                'required',
                'string',
                'max:255',
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'quantity' => [
                'required',
                'numeric',
                'gt:0',
            ],

            'unit' => [
                'required',
                'string',
                'max:50',
            ],

            'maximum_unit_price' => [
                'required',
                'numeric',
                'gte:0',
            ],

            'commission_per_unit' => [
                'required',
                'numeric',
                'gte:0',
            ],

            'photos' => [
                'nullable',
                'array',
                'max:3',
            ],

            'photos.*' => [
                'file',
                'image',
                'mimes:jpeg,jpg,png,webp',
                'max:5120',
            ],

            'required_by' => [
                'nullable',
                'date',
            ],

            'offer_deadline' => [
                'nullable',
                'date',
            ],

            'priority' => [
                'required',
                'string',
                'in:low,normal,high,urgent',
            ],

            'notes' => [
                'nullable',
                'string',
            ],
        ];
    }
}
