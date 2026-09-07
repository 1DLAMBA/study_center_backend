<?php

namespace App\Services;

use App\Models\ClearanceDepartment;
use App\Models\ClearanceDepartmentRequest;
use App\Models\ClearanceRequest;
use App\Models\GraduationList;
use App\Models\PersonalDetail;
use App\Support\CentreScope;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ClearanceRequestService
{
    public const ACCEPTANCE_AMOUNT = 8731;

    public function __construct(
        private readonly SchoolFeesGateService $schoolFeesGate
    ) {}

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

        if (!empty($filters['centre'])) {
            CentreScope::applyClearances($query, null, $filters['centre']);
        }

        return $query->get();
    }

    /**
     * Clearance gate: student must be on the uploaded graduation list, and
     * must have fully paid either last session (2024/2025, last-session DB)
     * or the current session (2025/2026).
     */
    private function assertOnGraduationList(PersonalDetail $personalDetail): void
    {
        if (! GraduationList::containsMatric($personalDetail->matric_number)) {
            throw ValidationException::withMessages([
                'graduation' => 'Student is not on the graduation list for this session.',
            ]);
        }
    }

    private function assertEligibleForClearance(PersonalDetail $personalDetail, bool $skipFeeGate = false): void
    {
        $this->assertOnGraduationList($personalDetail);

        if ($skipFeeGate) {
            return;
        }

        if (! $this->schoolFeesGate->hasPaidLastOrCurrentSession($personalDetail)) {
            throw ValidationException::withMessages([
                'payment' => 'Student must have fully paid school fees for either the last session (2024/2025) or the current session (2025/2026).',
            ]);
        }
    }

    private function isEligibleForClearance(?PersonalDetail $personalDetail): bool
    {
        if (! $personalDetail) {
            return false;
        }

        return GraduationList::containsMatric($personalDetail->matric_number)
            && $this->schoolFeesGate->hasPaidLastOrCurrentSession($personalDetail);
    }

    public function createForStaff(PersonalDetail $personalDetail, $feesReceiptFile = null, bool $skipFeeGate = false): ClearanceRequest
    {
        return $this->create([
            'personal_detail_id' => $personalDetail->id,
        ], $feesReceiptFile, $skipFeeGate);
    }

    public function create(array $data, $feesReceiptFile = null, bool $skipFeeGate = false): ClearanceRequest
    {
        $personalDetail = PersonalDetail::findOrFail($data['personal_detail_id']);

        $this->assertEligibleForClearance($personalDetail, $skipFeeGate);

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

    public function approve(
        ClearanceRequest $clearanceRequest,
        bool $forceFeeOverride = false,
        ?int $staffId = null
    ): ClearanceRequest {
        $personalDetail = $clearanceRequest->personalDetail;

        if (! $personalDetail) {
            throw ValidationException::withMessages([
                'payment' => 'Student record not found for this clearance request.',
            ]);
        }

        $this->assertEligibleForClearance($personalDetail, $forceFeeOverride);

        if ($forceFeeOverride && ! $clearanceRequest->fee_override) {
            $clearanceRequest->fee_override = true;
            $clearanceRequest->fee_override_by = $staffId;
            $clearanceRequest->fee_override_at = now();
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

        $clearanceRequest->status = ClearanceRequest::STATUS_APPROVED;
        $clearanceRequest->approved_at = now();
        $clearanceRequest->rejected_at = null;
        $clearanceRequest->rejection_reason = null;
        $clearanceRequest->save();

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
            if ($this->isEligibleForClearance($personalDetail)) {
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
