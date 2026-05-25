<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OverrideTaskDraftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:1000'],
            'task_type' => ['sometimes', 'nullable', 'string', 'max:100'],
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'summary' => ['sometimes', 'required', 'string'],
            'priority' => ['sometimes', 'required', Rule::in(['low', 'medium', 'high', 'critical'])],
            'suggested_team' => ['sometimes', 'nullable', 'string', 'max:100'],
            'confidence_score' => ['sometimes', 'required', 'numeric', 'between:0,1'],
            'missing_information' => ['sometimes', 'nullable', 'array'],
            'missing_information.*' => ['string'],
            'suggested_next_action' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
