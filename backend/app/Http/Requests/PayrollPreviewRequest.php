<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PayrollPreviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2020|max:2030',
            'scope' => 'nullable|string|in:all,department',
            'department_id' => 'nullable|integer',
            'parameters' => 'nullable|array',
            'adjustments' => 'nullable|array',
        ];
    }
}
