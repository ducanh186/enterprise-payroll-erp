<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PayrollAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => 'required|integer',
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2020|max:2030',
            'type' => 'required|in:bonus,deduction,allowance',
            'description' => 'required|string|max:500',
            'amount' => 'required|numeric|min:0',
            'is_taxable' => 'nullable|boolean',
            'code' => 'nullable|string|max:50',
        ];
    }
}
