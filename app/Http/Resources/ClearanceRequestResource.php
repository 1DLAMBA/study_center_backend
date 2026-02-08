<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClearanceRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $personalDetail = $this->relationLoaded('personalDetail') ? $this->personalDetail : null;
        $bioRegistration = $personalDetail && $personalDetail->relationLoaded('bioRegistration')
            ? $personalDetail->bioRegistration
            : null;

        return [
            'id' => $this->id,
            'personal_detail_id' => $this->personal_detail_id,
            'matric_number' => $this->matric_number,
            'status' => $this->status,
            'fees_receipt_path' => $this->fees_receipt_path,
            'fees_receipt_url' => $this->fees_receipt_path
                ? url("api/file/get/{$this->fees_receipt_path}")
                : null,
            'rejection_reason' => $this->rejection_reason,
            'approved_at' => $this->approved_at,
            'rejected_at' => $this->rejected_at,
            'acceptance_paid' => $this->acceptance_paid,
            'acceptance_reference' => $this->acceptance_reference,
            'acceptance_paid_at' => $this->acceptance_paid_at,
            'student' => $personalDetail ? [
                'id' => $personalDetail->id,
                'surname' => $personalDetail->surname,
                'other_names' => $personalDetail->other_names,
                'email' => $personalDetail->email,
                'phone_number' => $personalDetail->phone_number,
                'course' => $personalDetail->course,
                'school' => $personalDetail->school,
                'desired_study_cent' => $personalDetail->desired_study_cent,
                'application_number' => $personalDetail->application_number,
                'matric_number' => $personalDetail->matric_number,
                'has_paid' => $personalDetail->has_paid,
                'course_paid' => $personalDetail->course_paid,
                'level' => $bioRegistration?->level,
            ] : null,
            'departments' => $this->whenLoaded('departmentRequests', function () {
                return $this->departmentRequests->map(function ($departmentRequest) {
                    return [
                        'id' => $departmentRequest->id,
                        'department_id' => $departmentRequest->clearance_department_id,
                        'department_name' => $departmentRequest->department?->name,
                        'status' => $departmentRequest->status,
                        'reason' => $departmentRequest->reason,
                        'reviewed_at' => $departmentRequest->reviewed_at,
                    ];
                });
            }),
        ];
    }
}
