<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClearanceDepartmentRequest;
use App\Http\Requests\UpdateClearanceDepartmentRequest;
use App\Models\ClearanceDepartment;
use App\Services\ClearanceDepartmentService;

class ClearanceDepartmentController extends Controller
{
    public function __construct(private readonly ClearanceDepartmentService $service)
    {
    }

    public function index()
    {
        return response()->json($this->service->list());
    }

    public function store(StoreClearanceDepartmentRequest $request)
    {
        $department = $this->service->create($request->validated());

        return response()->json($department, 201);
    }

    public function update(UpdateClearanceDepartmentRequest $request, ClearanceDepartment $clearance_department)
    {
        $department = $this->service->update($clearance_department, $request->validated());

        return response()->json($department);
    }

    public function destroy(ClearanceDepartment $clearance_department)
    {
        $this->service->delete($clearance_department);

        return response()->json(['message' => 'Clearance department deleted.']);
    }
}
