<?php

namespace App\Http\Controllers;

use App\Imports\CourseDataImport;
use App\Models\CourseData;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class CourseDataController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv',
        ]);

        Excel::import(new CourseDataImport, $request->file('file'));

        return response()->json(['message' => 'File uploaded successfully']);
    }

    public function index()
    {
        $courses = CourseData::all();
        return response()->json($courses);
    }
}
