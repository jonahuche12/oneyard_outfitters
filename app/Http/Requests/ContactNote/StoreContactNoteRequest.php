<?php

namespace App\Http\Requests\ContactNote;

use App\Models\ContactNote;
use Illuminate\Foundation\Http\FormRequest;

class StoreContactNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', ContactNote::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'note' => ['required', 'string'],
        ];
    }
}
