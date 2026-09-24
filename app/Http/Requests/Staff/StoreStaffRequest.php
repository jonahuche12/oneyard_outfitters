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

            'password' => [
                'required',
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

    public function messages(): array
    {
        return [
            'roles.required' => 'At least one staff role must be selected.',
            'roles.min' => 'At least one staff role must be selected.',
        ];
    }
}