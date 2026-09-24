<?php

namespace App\Http\Requests\ContactNote;

use App\Models\ContactNote;
use Illuminate\Foundation\Http\FormRequest;

class UpdateContactNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        $contactNote = $this->route('contactNote');

        return $contactNote instanceof ContactNote
            && $this->user()?->can('update', $contactNote);
    }

    public function rules(): array
    {
        return [
            'note' => ['required', 'string'],
        ];
    }
}
