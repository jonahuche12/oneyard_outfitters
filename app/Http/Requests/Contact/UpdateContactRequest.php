<?php

namespace App\Http\Requests\Contact;

use App\Models\Contact;
use App\Models\Organization;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        $contact = $this->route('contact');

        return $contact instanceof Contact
            && $this->user()?->can('update', $contact);
    }

    public function rules(): array
    {
        return [
            'organization_id' => [
                'required',
                'integer',
                Rule::exists(Organization::class, 'id'),
            ],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'position' => ['nullable', 'string', 'max:150'],
            'phone' => ['required', 'string', 'max:50'],
            'alternate_phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'whatsapp' => ['nullable', 'string', 'max:50'],
            'is_primary' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
