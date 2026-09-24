<?php

namespace App\Http\Requests\FollowUp;

use App\Models\FollowUp;
use Illuminate\Foundation\Http\FormRequest;

class StoreFollowUpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', FollowUp::class) ?? false;
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
            ],

            'follow_up_date' => [
                'required',
                'date',
            ],

            'type' => [
                'required',
                'string',
                'in:call,visit,meeting,whatsapp,email,message,other',
            ],

            'subject' => [
                'required',
                'string',
                'max:200',
            ],

            'outcome' => [
                'required',
                'string',
            ],

            'next_action' => [
                'nullable',
                'string',
            ],

            'next_follow_up_date' => [
                'nullable',
                'date',
            ],

            'notes' => [
                'nullable',
                'string',
            ],
        ];
    }
}
