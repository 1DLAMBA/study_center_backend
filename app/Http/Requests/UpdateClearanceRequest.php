<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateClearanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'matric_number' => ['sometimes', 'string', 'regex:/^([A-Z]{2}\/)?[A-Z]{2}\/\d{2}\/\d{6}$/i'],
            'fees_receipt' => ['sometimes', 'file', 'mimes:pdf', 'max:2048'],
        ];
    }
}
