<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClearanceRequest;
use App\Http\Requests\UpdateClearanceRequest;
use App\Http\Resources\ClearanceRequestResource;
use App\Models\ClearanceDepartmentRequest;
use App\Models\ClearanceRequest;
use App\Services\ClearanceRequestService;
use Illuminate\Http\Request;

class ClearanceRequestController extends Controller
{
    public function __construct(private readonly ClearanceRequestService $service)
    {
    }

    public function index(Request $request)
    {
        $clearanceRequests = $this->service->list([
            'personal_detail_id' => $request->query('personal_detail_id'),
            'status' => $request->query('status'),
        ]);

        return ClearanceRequestResource::collection($clearanceRequests);
    }

    public function store(StoreClearanceRequest $request)
    {
        $clearanceRequest = $this->service->create(
            $request->validated(),
            $request->file('fees_receipt')
        );

        return (new ClearanceRequestResource($clearanceRequest))
            ->response()
            ->setStatusCode(201);
    }

    public function show(ClearanceRequest $clearance)
    {
        return new ClearanceRequestResource(
            $clearance->load(['personalDetail.bioRegistration', 'departmentRequests.department'])
        );
    }

    public function update(UpdateClearanceRequest $request, ClearanceRequest $clearance)
    {
        $updated = $this->service->update(
            $clearance,
            $request->validated(),
            $request->file('fees_receipt')
        );

        return new ClearanceRequestResource($updated);
    }

    public function destroy(ClearanceRequest $clearance)
    {
        $clearance->delete();

        return response()->json(['message' => 'Clearance request deleted.']);
    }

    public function approve(ClearanceRequest $clearance)
    {
        $approved = $this->service->approve($clearance);

        return new ClearanceRequestResource($approved);
    }

    public function reject(Request $request, ClearanceRequest $clearance)
    {
        $validated = $request->validate([
            'reason' => ['required', 'string'],
        ]);

        $rejected = $this->service->reject($clearance, $validated['reason']);

        return new ClearanceRequestResource($rejected);
    }

    public function updateDepartmentStatus(Request $request, ClearanceRequest $clearance, int $departmentId)
    {
        $validated = $request->validate([
            'status' => ['required', 'in:' . implode(',', [
                ClearanceDepartmentRequest::STATUS_PENDING,
                ClearanceDepartmentRequest::STATUS_APPROVED,
                ClearanceDepartmentRequest::STATUS_REJECTED,
            ])],
            'reason' => ['nullable', 'string'],
        ]);

        $updated = $this->service->updateDepartmentStatus(
            $clearance,
            $departmentId,
            $validated['status'],
            $validated['reason'] ?? null
        );

        return new ClearanceRequestResource($updated);
    }

    public function acceptanceConfig(ClearanceRequest $clearance)
    {
        $clearance->load(['personalDetail']);

        return response()->json([
            'id' => $clearance->id,
            'status' => $clearance->status,
            'acceptance_paid' => $clearance->acceptance_paid,
            'amount' => ClearanceRequestService::ACCEPTANCE_AMOUNT,
            'email' => $clearance->personalDetail?->email,
            'matric_number' => $clearance->matric_number,
        ]);
    }

    public function markAcceptancePaid(Request $request, ClearanceRequest $clearance)
    {
        $validated = $request->validate([
            'reference' => ['required', 'string'],
        ]);

        $updated = $this->service->markAcceptancePaid($clearance, $validated['reference']);

        return new ClearanceRequestResource($updated);
    }
}
