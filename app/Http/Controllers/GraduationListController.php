<?php

namespace App\Http\Controllers;

use App\Imports\GraduationListImport;
use App\Models\GraduationList;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class GraduationListController extends Controller
{
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv',
        ]);

        Excel::import(new GraduationListImport, $request->file('file'));

        return response()->json([
            'message' => 'Graduation list imported successfully',
            'total' => GraduationList::count(),
        ], 200);
    }

    public function index(Request $request)
    {
        $query = GraduationList::query()->orderBy('matric_number');

        $search = trim((string) $request->input('search', ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('matric_number', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        if ($centre = trim((string) $request->input('centre', ''))) {
            $query->where('centre', $centre);
        }

        return response()->json($query->paginate($request->integer('per_page', 20)));
    }

    /**
     * Always 200 + boolean so the SPA can avoid try/catch on 404
     * (mirrors PersonalDetailController::checkMatric semantics).
     */
    public function check(string $matricNumber)
    {
        $normalized = GraduationList::normalizeMatric($matricNumber);

        $entry = $normalized
            ? GraduationList::where('matric_number', $normalized)->first()
            : null;

        return response()->json([
            'on_list' => $entry !== null,
            'entry' => $entry,
        ], 200);
    }
}
