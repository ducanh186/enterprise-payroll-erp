<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ManualCheckinRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => 'required|integer',
            'check_time' => 'required|date',
            'check_type' => 'required|in:in,out',
            'reason' => 'nullable|string|max:500',
        ];
    }
}
