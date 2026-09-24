<?php

namespace App\Http\Requests\Assessment;

use App\Models\Assessment;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAssessmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $assessment = $this->route('assessment');

        return $assessment instanceof Assessment
            && $this->user()?->can('update', $assessment);
    }

    public function rules(): array
    {
        return [
            'assessment_date' => ['required', 'date'],
            'status' => ['required', 'string', 'in:draft,completed'],
            'needs' => ['required', 'string'],
            'current_supplier' => ['nullable', 'string', 'max:200'],
            'procurement_process' => ['nullable', 'string'],
            'estimated_budget' => ['nullable', 'numeric', 'min:0', 'max:9999999999999.99'],
            'estimated_demand' => ['nullable', 'string'],
            'buying_timeline' => ['nullable', 'string', 'max:100'],
            'decision_maker' => ['nullable', 'string', 'max:200'],
            'observations' => ['nullable', 'string'],
            'opportunities' => ['nullable', 'string'],
            'risks' => ['nullable', 'string'],
            'recommendation' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
