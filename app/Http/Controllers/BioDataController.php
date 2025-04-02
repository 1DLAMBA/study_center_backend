<?php

namespace App\Http\Controllers;

use App\Models\BioData;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBioDataRequest;
use App\Models\Application;
use Illuminate\Http\Request;

class BioDataController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreBioDataRequest $request)
    {
        $validated = $request->validated();
        $email = $request->input('email');
      
        $bioData = BioData::updateOrCreate([ 'application_id'=>$request->application_id], $validated);
        $application = Application::where('id', $request->application_id)->first();
        $application->email = $email;
        $application->save();

        return response()->json([
            'message' => 'BioData created successfully.',
            'data' => $bioData,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $bioData = BioData::with('application')->where('application_id',$id)->get();

        return response()->json([
            'data' => $bioData,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(BioData $bioData)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, BioData $bioData)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(BioData $bioData)
    {
        //
    }
}
