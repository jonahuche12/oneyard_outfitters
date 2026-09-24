<?php

namespace App\Http\Requests;

use App\Models\Contact;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductSpecificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('specifications.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'organization_id' => [
                'required',
                'integer',
                'exists:organizations,id',
            ],

            'contact_id' => [
                'nullable',
                'integer',
                'exists:contacts,id',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($value === null) {
                        return;
                    }

                    $belongsToOrganization = Contact::query()
                        ->whereKey($value)
                        ->where(
                            'organization_id',
                            $this->input('organization_id')
                        )
                        ->exists();

                    if (! $belongsToOrganization) {
                        $fail(
                            'The selected contact does not belong to the selected organization.'
                        );
                    }
                },
            ],

            'specification_date' => [
                'required',
                'date',
            ],

            'item_name' => [
                'required',
                'string',
                'max:200',
            ],

            'product_type' => [
                'required',
                'string',
                Rule::in([
                    'uniform',
                    'sportswear',
                    'bag',
                    'shoe',
                    'branded_item',
                    'school_supply',
                    'other',
                ]),
            ],

            'description' => [
                'required',
                'string',
            ],

            'unit' => [
                'required',
                'string',
                'max:50',
            ],

            'unit_price' => [
                'required',
                'numeric',
                'min:0',
            ],

            'price_updated_at' => [
                'nullable',
                'date',
            ],

            'material' => [
                'nullable',
                'string',
            ],

            'material_details' => [
                'nullable',
                'string',
            ],

            'design_details' => [
                'nullable',
                'string',
            ],

            'size_details' => [
                'nullable',
                'string',
            ],

            'branding_details' => [
                'nullable',
                'string',
            ],

            'quality_requirements' => [
                'nullable',
                'string',
            ],

            'special_instructions' => [
                'nullable',
                'string',
            ],

            'status' => [
                'sometimes',
                'string',
                Rule::in([
                    'draft',
                    'confirmed',
                    'cancelled',
                ]),
            ],

            'notes' => [
                'nullable',
                'string',
            ],
        ];
    }
}