<?php

namespace App\Http\Controllers;

use App\Models\PersonalDetail;
use App\Models\StudentDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class StudentDetailController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json(StudentDetail::with('personalDetail')->get(), 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'application_number' => 'nullable',
            'first_school' => 'nullable|string|max:255',
            'first_course' => 'nullable|string|max:255',
            'p_school_name_1' => 'nullable|string|max:255',
            'p_school_from_1' => 'nullable|date',
            'p_school_to_1' => 'nullable|date',
            'p_school_name_2' => 'nullable|string|max:255',
            'p_school_from_2' => 'nullable|date',
            'p_school_to_2' => 'nullable|date',
            's_school_name_1' => 'nullable|string|max:255',
            's_school_from_1' => 'nullable|date',
            's_school_to_1' => 'nullable|date',
            's_school_name_2' => 'nullable|string|max:255',
            's_school_from_2' => 'nullable|date',
            's_school_to_2' => 'nullable|date',
            'second_school' => 'nullable|string|max:255',
            'second_course' => 'nullable|string|max:255',
        ]);

        $studentDetail = StudentDetail::create($validated);
        return response()->json($studentDetail, 201);
    }

    /**
     * Display the specified resource.
     */
    
    public function show(string $id)
    {
        $student = StudentDetail::where('application_number', $id)->first();
        return response()->json($student);
    }
    
    
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'new_course' => 'required|string|max:255',
            'new_school' => 'required|string|max:255',
        ]);
        Log::info($validated);

        $studentDetails = StudentDetail::where('application_number', $id)->first();
        $personalDetail = PersonalDetail::where('id', $id)->first();

        // PersonalDetail is the source of truth for course/school. Imported
        // students may lack a student_details row entirely, so only require
        // PersonalDetail for the 422 short-circuit.
        if (!$personalDetail) {
            return response()->json([
                'message' => 'Student record not found for the given id',
            ], 422);
        }

        $personalDetail->course = $validated['new_course'];
        $personalDetail->school = $validated['new_school'];

        if ($studentDetails) {
            $studentDetails->first_school = $validated['new_school'];
            $studentDetails->first_course = $validated['new_course'];
        }

        // For matriculated students the matric must reflect the new department
        // code, so regenerate and mirror the value into application_number to
        // match the invariant maintained by PaymentController on acceptance.
        // Pending students (no matric yet) keep matric assignment with the
        // acceptance flow / PersonalDetailController::update.
        if ($personalDetail->matric_number) {
            $matricNumber = PersonalDetail::generateMatricNumber(
                $validated['new_course'],
                $personalDetail->desired_study_cent
            );
            $personalDetail->matric_number = $matricNumber;
            $personalDetail->application_number = $matricNumber;
        }

        $personalDetail->save();
        if ($studentDetails) {
            $studentDetails->save();
        }

        return response()->json([
            'message' => 'Course and School updated successfully',
            'data' => $studentDetails,
            'personal_detail' => [
                'matric_number' => $personalDetail->matric_number,
                'application_number' => $personalDetail->application_number,
                'course' => $personalDetail->course,
                'school' => $personalDetail->school,
            ],
        ], 200);
    }
}
