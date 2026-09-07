<?php

namespace App\Http\Controllers;

use App\Imports\GraduationListImport;
use App\Models\GraduationList;
use App\Models\PersonalDetail;
use App\Support\CentreScope;
use App\Support\StaffPermissions;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

class GraduationListController extends Controller
{
    public function downloadSample()
    {
        $filename = 'graduation_list_sample.csv';

        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            // BOM so Excel opens UTF-8 cleanly
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['MATRIC NO', 'NAME', 'COURSE', 'CENTRE']);
            fputcsv($out, ['ADA/MK/22/199001', 'Ada Lovelace', 'Mathematics / Geography', 'suleja']);
            fputcsv($out, ['GRC/MK/22/199002', 'Grace Hopper', 'Primary Education Studies (Double Major)', 'Bida']);
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv',
        ]);

        $lockCentre = StaffPermissions::isCoordinator($request->user())
            ? $request->user()->study_centre
            : null;

        Excel::import(new GraduationListImport($lockCentre), $request->file('file'));

        $report = $this->matchReport($request);

        return response()->json([
            'message' => 'Graduation list imported successfully',
            'total' => $report['total'],
            'matched' => $report['matched'],
            'unmatched' => $report['unmatched'],
            'unmatched_rows' => $report['unmatched_rows'],
        ], 200);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'matric_number' => ['required', 'string', 'max:80'],
            'name' => ['required', 'string', 'max:255'],
            'course' => ['nullable', 'string', 'max:255'],
            'centre' => ['nullable', 'string', 'max:100'],
            'session' => ['nullable', 'string', 'max:20'],
        ]);

        $matric = GraduationList::normalizeMatric($validated['matric_number']);
        if ($matric === null) {
            throw ValidationException::withMessages([
                'matric_number' => 'Enter a valid matric number.',
            ]);
        }

        $user = $request->user();
        $centre = trim((string) ($validated['centre'] ?? ''));
        if (StaffPermissions::isCoordinator($user)) {
            $centre = (string) $user->study_centre;
        }

        if ($centre === '') {
            throw ValidationException::withMessages([
                'centre' => 'Select a study centre.',
            ]);
        }

        $existing = GraduationList::where('matric_number', $matric)->first();
        if ($existing) {
            CentreScope::assertGraduationCentre($user, $existing->centre);
        }

        $name = trim(preg_replace('/\s+/', ' ', (string) $validated['name']));
        $course = isset($validated['course']) ? trim((string) $validated['course']) : null;
        $session = trim((string) ($validated['session'] ?? ''));

        $row = GraduationList::updateOrCreate(
            ['matric_number' => $matric],
            [
                'name' => $name !== '' ? $name : null,
                'course' => $course !== '' ? $course : null,
                'centre' => $centre,
                'session' => $session !== '' ? $session : ($existing?->session ?: '2025/2026'),
            ]
        );

        $report = $this->matchReport($request);

        return response()->json([
            'message' => $existing
                ? 'Graduation list row updated.'
                : 'Student added to the graduation list.',
            'data' => $row,
            'total' => $report['total'],
            'matched' => $report['matched'],
            'unmatched' => $report['unmatched'],
            'unmatched_rows' => $report['unmatched_rows'],
        ], $existing ? 200 : 201);
    }

    public function index(Request $request)
    {
        $query = GraduationList::query()->orderBy('matric_number');

        CentreScope::applyGraduation($query, $request->user(), $request->input('centre'));

        $search = trim((string) $request->input('search', ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('matric_number', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        return response()->json($query->paginate($request->integer('per_page', 20)));
    }

    /**
     * Graduation-list rows with no matching personal_details record.
     */
    public function unmatched(Request $request)
    {
        return response()->json($this->matchReport($request));
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

    private function matchReport(Request $request): array
    {
        $query = GraduationList::query()->orderBy('matric_number');
        CentreScope::applyGraduation($query, $request->user(), $request->input('centre'));
        $rows = $query->get();

        $lookup = [];
        PersonalDetail::query()
            ->whereNotNull('matric_number')
            ->get(['id', 'matric_number', 'other_names', 'course', 'desired_study_cent'])
            ->each(function (PersonalDetail $detail) use (&$lookup) {
                $key = GraduationList::normalizeMatric($detail->matric_number);
                if ($key) {
                    $lookup[$key] = $detail;
                }
            });

        $unmatchedRows = [];
        $matched = 0;

        foreach ($rows as $row) {
            $key = GraduationList::normalizeMatric($row->matric_number);
            if ($key && isset($lookup[$key])) {
                $matched++;
                continue;
            }

            $unmatchedRows[] = [
                'id' => $row->id,
                'matric_number' => $row->matric_number,
                'name' => $row->name,
                'course' => $row->course,
                'centre' => $row->centre,
                'session' => $row->session,
            ];
        }

        return [
            'total' => $rows->count(),
            'matched' => $matched,
            'unmatched' => count($unmatchedRows),
            'unmatched_rows' => $unmatchedRows,
        ];
    }
}
