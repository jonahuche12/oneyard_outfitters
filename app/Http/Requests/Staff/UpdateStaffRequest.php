<?php

namespace App\Http\Requests\Staff;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        $staff = $this->route('user');

        return $staff instanceof User
            && $this->user()?->can('update', $staff);
    }

    public function rules(): array
    {
        $staff = $this->route('user');

        $canAssignRoles = $staff instanceof User
            && $this->user()?->can('assignRoles', $staff);

        return [
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($staff),
            ],

            'phone' => [
                'nullable',
                'string',
                'regex:/^\+[1-9]\d{7,14}$/',
            ],

            'password' => [
                'nullable',
                'string',
                'min:8',
                'confirmed',
            ],

            'roles' => [
                Rule::requiredIf($canAssignRoles),
                'nullable',
                'array',
                'min:1',
            ],

            'roles.*' => [
                'integer',
                'distinct',
                Rule::exists('roles', 'id'),
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('phone')) {
            $phone = preg_replace('/[\s\-().]/', '', (string) $this->input('phone'));

            $this->merge([
                'phone' => $phone === '' ? null : $phone,
            ]);
        }
    }

    public function messages(): array
    {
        return [
            'roles.required' => 'At least one staff role must be selected.',
            'roles.min' => 'At least one staff role must be selected.',
            'phone.regex' => 'Enter the phone number in international format, for example +2348012345678.',
        ];
    }
}