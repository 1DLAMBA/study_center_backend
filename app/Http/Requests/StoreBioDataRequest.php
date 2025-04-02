<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBioDataRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'application_id' => 'required|exists:applications,id',
            'full_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone_number' => 'nullable|string|max:20',
            'gender' => 'nullable|string|max:10',
            'date_of_birth' => 'nullable|date',
            'place_of_birth' => 'nullable|string|max:255',
            'marital_status' => 'nullable|string|max:50',
            'religion' => 'nullable|string|max:50',
            'nationality' => 'nullable|string|max:100',
            'faculty' => 'nullable|string|max:255',
            'department' => 'nullable|string|max:255',
            'programme' => 'nullable|string|max:255',
            'level' => 'nullable|string|max:10',
            'current_semester' => 'nullable|string|max:10',
            'current_session' => 'nullable|string|max:20',
            'matric_number' => 'nullable|string|max:50',
            'mode_of_entry' => 'nullable|string|max:50',
            'study_mode' => 'nullable|string|max:50',
            'entry_year' => 'nullable|string|max:10',
            'program_duration' => 'nullable|string|max:10',
            'award_in_view' => 'nullable|string|max:255',
            'present_contact_address' => 'nullable|string|max:255',
            'permanent_home_address' => 'nullable|string|max:255',
            'next_of_kin' => 'nullable|string|max:255',
            'next_of_kin_phone_number' => 'nullable|string|max:20',
            'next_of_kin_relationship' => 'nullable|string|max:50',
            'sponsor_address' => 'nullable|string|max:255',
        ];
    }
}
