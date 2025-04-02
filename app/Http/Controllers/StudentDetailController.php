<?php

namespace App\Http\Controllers;

use App\Models\StudentDetail;
use Illuminate\Http\Request;

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
}
