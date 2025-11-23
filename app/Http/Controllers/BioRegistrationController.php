<?php

namespace App\Http\Controllers;

use App\Models\BioRegistration;
use App\Models\PersonalDetail;
use App\Models\StudentDetail;
use Illuminate\Http\Request;

class BioRegistrationController extends Controller
{
    // Store a new record

    public function index()
    {
        return response()->json(BioRegistration::with('personalDetails')->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'application_number' => 'nullable',
            'next_of_kin' => 'nullable|string',
            'sponsor_address' => 'nullable|string',
            'next_of_kin_relationship' => 'nullable|string',
            'next_of_kin_phone_number' => 'nullable|string',
            'next_of_kin_address' => 'nullable|string',
            'place_of_birth' => 'nullable|string',
            'nationality' => 'nullable|string',
            'mode_of_entry' => 'nullable|string',
            'session' => 'nullable|string',
            'level' => 'nullable|string',
            'subject_combination' => 'nullable|string',
        ]);
        $userCheck = BioRegistration::where('application_number', $validated['application_number'])->first();
        if ($userCheck) {
            $userCheck->update($validated);

            return response()->json(['message' => 'Bio Registration already exists.', 'data' => $userCheck], 200);
        }
        $bioRegistration = BioRegistration::create($validated);

        return response()->json(['message' => 'Bio Registration created successfully.', 'data' => $bioRegistration], 201);
    }

    public function approve_prence(Request $request)
    {
        $validated = $request->validate([
            'application_number' => 'nullable',
            'mode_of_entry' => 'nullable|string',
        ]);
        $bioRegistration = BioRegistration::create($validated);
        $personalDetail = PersonalDetail::where('id', $validated['application_number'])->first();
        $studentDetails = StudentDetail::where('application_number',  $validated['application_number'])->first();
        $personalDetail->course = $studentDetails->first_course;
        $personalDetail->has_admission = true;
        $personalDetail->save();

        return response()->json(['message' => 'Approved to Pre NCE successfully.', 'data' => $bioRegistration], 201);
    }
    // Show a record
    public function show($id)
    {
        $bioRegistration = BioRegistration::where('application_number', $id)->first();
        return response()->json($bioRegistration);
    }

    // Update a record
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'application_number' => 'required|exists:personal_details,application_number',
            'next_of_kin' => 'nullable|string',
            'sponsor_address' => 'nullable|string',
            'next_of_kin_relationship' => 'nullable|string',
            'next_of_kin_phone_number' => 'nullable|string',
            'next_of_kin_address' => 'nullable|string',
            'nationality' => 'nullable|string',
            'level' => 'nullable|string',
            'mode_of_entry' => 'nullable|string',
            'place_of_birth' => 'nullable|string',
            'session' => 'nullable|string',
            'subject_combination' => 'nullable|string',
        ]);

        $bioRegistration = BioRegistration::findOrFail($id);
        $bioRegistration->update($validated);

        return response()->json(['message' => 'Bio Registration updated successfully.', 'data' => $bioRegistration]);
    }

    // Delete a record
    public function destroy($id)
    {
        $bioRegistration = BioRegistration::findOrFail($id);
        $bioRegistration->delete();

        return response()->json(['message' => 'Bio Registration deleted successfully.']);
    }
}
