<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CourseController extends Controller
{
    // Display a listing of courses
    public function index()
    {
        $courses = Course::all();
        return response()->json($courses);
    }

    // Store a newly created course in the database
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'course' => 'nullable',
            'application_id' => 'nullable',
            'mode_of_course' => 'nullable',
            'subject_of_study' => 'nullable',
            'session' => 'nullable',
            'semester' => 'nullable',
            'course_type' => 'nullable',
            'level_of_course' => 'nullable',
        ]);
        Log::debug('PAYLOAD'. $request);

        $course = Course::create($validatedData);

        return response()->json(['message' => 'Course created successfully', 'data' => $course]);
    }

    // Display a specific course
    public function show($id)
    {
        $course = Course::where('application_id',$id)->get();

        if (!$course) {
            return response()->json(['message' => 'Course not found'], 404);
        }

        return response()->json($course);
    }

    // Update a specific course
    public function update(Request $request, $id)
    {
        $course = Course::find($id);

        if (!$course) {
            return response()->json(['message' => 'Course not found'], 404);
        }

        $validatedData = $request->validate([
            'course' => 'nullable|string|max:255',
            'course_code' => 'nullable|string|max:255',
        ]);

        $course->update($validatedData);

        return response()->json(['message' => 'Course updated successfully', 'data' => $course]);
    }

    // Delete a specific course
    public function destroy($id)
    {
        $course = Course::find($id);

        if (!$course) {
            return response()->json(['message' => 'Course not found'], 404);
        }

        $course->delete();

        return response()->json(['message' => 'Course deleted successfully']);
    }
}
