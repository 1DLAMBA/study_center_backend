<?php

namespace App\Services;

use App\Models\ClearanceDepartment;
use App\Models\ClearanceDepartmentRequest;
use App\Models\ClearanceRequest;
use App\Models\PersonalDetail;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ClearanceRequestService
{
    public const ACCEPTANCE_AMOUNT = 8731;

    public function list(array $filters = [])
    {
        $query = ClearanceRequest::with([
            'personalDetail.bioRegistration',
            'departmentRequests.department',
        ])->latest();

        if (!empty($filters['personal_detail_id'])) {
            $query->where('personal_detail_id', $filters['personal_detail_id']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->get();
    }

    public function create(array $data, $feesReceiptFile = null): ClearanceRequest
    {
        $personalDetail = PersonalDetail::findOrFail($data['personal_detail_id']);

        if (!$personalDetail->has_paid || !$personalDetail->course_paid) {
            throw ValidationException::withMessages([
                'payment' => 'Student has not completed school fees.',
            ]);
        }

        $existing = ClearanceRequest::where('personal_detail_id', $personalDetail->id)
            ->whereIn('status', [ClearanceRequest::STATUS_PENDING, ClearanceRequest::STATUS_APPROVED])
            ->latest()
            ->first();

        if ($existing) {
            throw ValidationException::withMessages([
                'clearance' => 'A clearance request already exists for this student.',
            ]);
        }

        if ($feesReceiptFile) {
            $feesReceiptFile->store('public/files');
            $data['fees_receipt_path'] = $feesReceiptFile->hashName();
        }

        return DB::transaction(function () use ($data, $personalDetail) {
            $clearanceRequest = ClearanceRequest::create([
                'personal_detail_id' => $data['personal_detail_id'],
                'matric_number' => $personalDetail->matric_number,
                'fees_receipt_path' => $data['fees_receipt_path'] ?? null,
                'status' => ClearanceRequest::STATUS_PENDING,
            ]);

            $departments = ClearanceDepartment::where('is_active', true)->get();
            foreach ($departments as $department) {
                ClearanceDepartmentRequest::create([
                    'clearance_request_id' => $clearanceRequest->id,
                    'clearance_department_id' => $department->id,
                    'status' => ClearanceDepartmentRequest::STATUS_PENDING,
                ]);
            }

            return $clearanceRequest->load(['personalDetail.bioRegistration', 'departmentRequests.department']);
        });
    }

    public function update(ClearanceRequest $clearanceRequest, array $data, $feesReceiptFile = null): ClearanceRequest
    {
        if ($feesReceiptFile) {
            $feesReceiptFile->store('public/files');
            $data['fees_receipt_path'] = $feesReceiptFile->hashName();
        }

        $clearanceRequest->update([
            'matric_number' => $data['matric_number'] ?? $clearanceRequest->matric_number,
            'fees_receipt_path' => $data['fees_receipt_path'] ?? $clearanceRequest->fees_receipt_path,
        ]);

        return $clearanceRequest->load(['personalDetail.bioRegistration', 'departmentRequests.department']);
    }

    public function approve(ClearanceRequest $clearanceRequest): ClearanceRequest
    {
        $personalDetail = $clearanceRequest->personalDetail;

        if (!$personalDetail || !$personalDetail->has_paid || !$personalDetail->course_paid) {
            throw ValidationException::withMessages([
                'payment' => 'Student has not completed school fees.',
            ]);
        }

        $pendingDepartments = $clearanceRequest->departmentRequests()
            ->where('status', ClearanceDepartmentRequest::STATUS_PENDING)
            ->count();

        if ($pendingDepartments > 0) {
            throw ValidationException::withMessages([
                'departments' => 'All departments must be cleared before approval.',
            ]);
        }

        $rejectedDepartments = $clearanceRequest->departmentRequests()
            ->where('status', ClearanceDepartmentRequest::STATUS_REJECTED)
            ->count();

        if ($rejectedDepartments > 0) {
            throw ValidationException::withMessages([
                'departments' => 'Request has a rejected department.',
            ]);
        }

        $clearanceRequest->update([
            'status' => ClearanceRequest::STATUS_APPROVED,
            'approved_at' => now(),
            'rejected_at' => null,
            'rejection_reason' => null,
        ]);

        return $clearanceRequest->load(['personalDetail.bioRegistration', 'departmentRequests.department']);
    }

    public function reject(ClearanceRequest $clearanceRequest, string $reason): ClearanceRequest
    {
        $clearanceRequest->update([
            'status' => ClearanceRequest::STATUS_REJECTED,
            'rejection_reason' => $reason,
            'rejected_at' => now(),
        ]);

        return $clearanceRequest->load(['personalDetail.bioRegistration', 'departmentRequests.department']);
    }

    public function updateDepartmentStatus(
        ClearanceRequest $clearanceRequest,
        int $departmentId,
        string $status,
        ?string $reason = null,
        ?int $reviewedBy = null
    ): ClearanceRequest {
        $departmentRequest = $clearanceRequest->departmentRequests()
            ->where('clearance_department_id', $departmentId)
            ->firstOrFail();

        $departmentRequest->update([
            'status' => $status,
            'reason' => $reason,
            'reviewed_by' => $reviewedBy,
            'reviewed_at' => now(),
        ]);

        $this->recalculateStatus($clearanceRequest);

        return $clearanceRequest->load(['personalDetail.bioRegistration', 'departmentRequests.department']);
    }

    public function recalculateStatus(ClearanceRequest $clearanceRequest): void
    {
        $requests = $clearanceRequest->departmentRequests()->get();
        $hasRejected = $requests->contains('status', ClearanceDepartmentRequest::STATUS_REJECTED);
        $allApproved = $requests->count() > 0
            && $requests->every(fn ($item) => $item->status === ClearanceDepartmentRequest::STATUS_APPROVED);

        if ($hasRejected) {
            $clearanceRequest->update([
                'status' => ClearanceRequest::STATUS_REJECTED,
                'rejected_at' => now(),
            ]);

            return;
        }

        if ($allApproved) {
            $personalDetail = $clearanceRequest->personalDetail;
            if ($personalDetail && $personalDetail->has_paid && $personalDetail->course_paid) {
                $clearanceRequest->update([
                    'status' => ClearanceRequest::STATUS_APPROVED,
                    'approved_at' => now(),
                ]);
            }

            return;
        }

        $clearanceRequest->update([
            'status' => ClearanceRequest::STATUS_PENDING,
        ]);
    }

    public function markAcceptancePaid(ClearanceRequest $clearanceRequest, string $reference): ClearanceRequest
    {
        if ($clearanceRequest->status !== ClearanceRequest::STATUS_APPROVED) {
            throw ValidationException::withMessages([
                'clearance' => 'Clearance is not approved for payment.',
            ]);
        }

        $clearanceRequest->update([
            'acceptance_paid' => true,
            'acceptance_reference' => $reference,
            'acceptance_paid_at' => now(),
        ]);

        return $clearanceRequest;
    }
}
