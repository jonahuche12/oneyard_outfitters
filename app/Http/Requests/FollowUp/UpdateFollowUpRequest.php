<?php

namespace App\Http\Requests\FollowUp;

use App\Models\FollowUp;
use Illuminate\Foundation\Http\FormRequest;

class UpdateFollowUpRequest extends FormRequest
{
    public function authorize(): bool
    {
        $followUp = $this->route('follow_up');

        return $followUp instanceof FollowUp
            && $this->user()?->can('update', $followUp);
    }

    public function rules(): array
    {
        return [
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

            'status' => [
                'required',
                'string',
                'in:open,completed,cancelled',
            ],

            'notes' => [
                'nullable',
                'string',
            ],
        ];
    }
}
