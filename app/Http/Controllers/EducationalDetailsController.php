<?php

namespace App\Http\Controllers;

use App\Models\EducationalDetail;
use Illuminate\Http\Request;

class EducationalDetailsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // Retrieve all educational details
        $educationalDetail = EducationalDetail::with('personalDetail')->get();
        return response()->json($educationalDetail);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Validate incoming data
        $validated = $request->validate([
            'application_number' => 'nullable',
            'exam_type' => 'required|string',
            'exam_number' => 'required|string',
            'exam_month' => 'required|string',
            'exam_year' => 'required|digits:4',
            'subject_1' => 'required|string',
            'grade_1' => 'required|string',
            'subject_2' => 'nullable|string',
            'grade_2' => 'nullable|string',
            'subject_3' => 'nullable|string',
            'grade_3' => 'nullable|string',
            'subject_4' => 'nullable|string',
            'grade_4' => 'nullable|string',
            'subject_5' => 'nullable|string',
            'grade_5' => 'nullable|string',
            'subject_6' => 'nullable|string',
            'grade_6' => 'nullable|string',
            'subject_7' => 'nullable|string',
            'grade_7' => 'nullable|string',
            'subject_8' => 'nullable|string',
            'grade_8' => 'nullable|string',
            'subject_9' => 'nullable|string',
            'grade_9' => 'nullable|string',
            'uploaded_ssce' => 'nullable|string',
        ]);

        // Create a new record
        $educationalDetail = EducationalDetail::create($validated);

        return response()->json([
            'message' => 'Educational details created successfully!',
            'data' => $educationalDetail,
        ], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  string  $application_number
     * @return \Illuminate\Http\Response
     */
    public function show($application_number)
    {
        // Find by application_number and include personal details
        $educationalDetail = EducationalDetail::with('personalDetail')
            ->where('application_number', $application_number)
            ->first();

        if (!$educationalDetail) {
            return response()->json(['message' => 'Educational details not found'], 404);
        }

        return response()->json($educationalDetail);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $application_number
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $application_number)
    {
        // Validate incoming data
        $validated = $request->validate([
            'exam_type' => 'nullable|string',
            'exam_number' => 'nullable|string',
            'exam_month' => 'nullable|string',
            'exam_year' => 'nullable|digits:4',
            'subject_1' => 'nullable|string',
            'grade_1' => 'nullable|string',
            'subject_2' => 'nullable|string',
            'grade_2' => 'nullable|string',
            'subject_3' => 'nullable|string',
            'grade_3' => 'nullable|string',
            'subject_4' => 'nullable|string',
            'grade_4' => 'nullable|string',
            'subject_5' => 'nullable|string',
            'grade_5' => 'nullable|string',
            'subject_6' => 'nullable|string',
            'grade_6' => 'nullable|string',
            'subject_7' => 'nullable|string',
            'grade_7' => 'nullable|string',
            'subject_8' => 'nullable|string',
            'grade_8' => 'nullable|string',
            'subject_9' => 'nullable|string',
            'grade_9' => 'nullable|string',
            'uploaded_ssce' => 'nullable|string',
        ]);

        // Find the record by application_number
        $educationalDetail = EducationalDetail::where('application_number', $application_number)->first();

        if (!$educationalDetail) {
            return response()->json(['message' => 'Educational details not found'], 404);
        }

        // Update the record
        $educationalDetail->update($validated);

        return response()->json([
            'message' => 'Educational details updated successfully!',
            'data' => $educationalDetail,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  string  $application_number
     * @return \Illuminate\Http\Response
     */
    public function destroy($application_number)
    {
        // Find the record by application_number
        $educationalDetail = EducationalDetail::where('application_number', $application_number)->first();

        if (!$educationalDetail) {
            return response()->json(['message' => 'Educational details not found'], 404);
        }

        // Delete the record
        $educationalDetail->delete();

        return response()->json(['message' => 'Educational details deleted successfully!']);
    }
}
