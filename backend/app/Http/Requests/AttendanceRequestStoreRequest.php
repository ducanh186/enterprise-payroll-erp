<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AttendanceRequestStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => 'required|integer',
            'request_type' => 'required|in:late_excuse,missing_checkout,leave,overtime,early_leave',
            'request_date' => 'required|date',
            'reason' => 'required|string|max:1000',
        ];
    }
}
