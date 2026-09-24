<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductSpecificationArtifactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('specifications.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'artifact_type' => [
                'required',
                'string',
                Rule::in([
                    'design',
                    'material',
                    'logo',
                    'branding',
                    'sample',
                    'size_chart',
                    'measurement',
                    'reference',
                    'other',
                ]),
            ],

            'title' => [
                'required',
                'string',
                'max:200',
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'file' => [
                'required',
                'file',
                'mimes:jpg,jpeg,png,webp,pdf',
                'max:10240',
            ],
        ];
    }
}
