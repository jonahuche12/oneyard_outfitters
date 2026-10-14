<?php

namespace App\Http\Requests\Staff;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', User::class) ?? false;
    }

    public function rules(): array
    {
        $canAssignRoles = $this->user()?->hasPermission('users.assign-roles') ?? false;

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
                'unique:users,email',
            ],

            'phone' => [
                'required',
                'string',
                'regex:/^\+[1-9]\d{7,14}$/',
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
        if ($this->has('email')) {
            $this->merge(['email' => strtolower(trim((string) $this->input('email')))]);
        }

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
            'phone.required' => 'A WhatsApp phone number is required for staff onboarding.',
            'phone.regex' => 'Enter the phone number in international format, for example +2348012345678.',
        ];
    }
}