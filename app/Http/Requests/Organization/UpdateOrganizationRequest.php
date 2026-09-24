<?php

namespace App\Http\Requests\Organization;

use App\Models\Organization;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrganizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $organization = $this->route('organization');

        return $organization instanceof Organization
            && $this->user()?->can('update', $organization);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:200'],

            'type' => [
                'required',
                'string',
                Rule::in([
                    'school',
                    'company',
                    'church',
                    'ngo',
                    'government',
                    'association',
                    'other',
                ]),
            ],

            'ownership' => [
                'required',
                'string',
                Rule::in([
                    'private',
                    'public',
                    'government',
                    'non_profit',
                    'other',
                ]),
            ],

            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:100'],
            'area' => ['nullable', 'string', 'max:100'],
            'lga' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'website' => ['nullable', 'url', 'max:255'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
