<?php

namespace App\Http\Controllers;

use App\Http\Resources\ClearanceRequestResource;
use App\Models\ClearanceRequest;
use App\Models\PersonalDetail;
use App\Services\ClearanceRequestService;
use App\Support\CentreScope;
use Illuminate\Http\Request;

class StaffClearanceController extends Controller
{
    public function __construct(private readonly ClearanceRequestService $service)
    {
    }

    public function index(Request $request)
    {
        $filters = [
            'status' => $request->query('status'),
            'centre' => CentreScope::effectiveCentre($request->user(), $request->query('centre')),
        ];

        $clearanceRequests = $this->service->list($filters);

        return ClearanceRequestResource::collection($clearanceRequests);
    }

    public function start(Request $request)
    {
        $validated = $request->validate([
            'personal_detail_id' => ['required', 'exists:personal_details,id'],
            'fees_receipt' => ['nullable', 'file', 'mimes:pdf', 'max:5120'],
        ]);

        $personalDetail = PersonalDetail::findOrFail($validated['personal_detail_id']);
        CentreScope::assertStudentCentre($request->user(), $personalDetail->desired_study_cent);

        $clearanceRequest = $this->service->createForStaff(
            $personalDetail,
            $request->file('fees_receipt'),
            skipFeeGate: true
        );

        return (new ClearanceRequestResource($clearanceRequest))
            ->response()
            ->setStatusCode(201);
    }
}
