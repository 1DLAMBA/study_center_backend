<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreClearanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'personal_detail_id' => ['required', 'exists:personal_details,id'],
            'fees_receipt' => ['required', 'file', 'mimes:pdf', 'max:5120'],
        ];
    }
}
