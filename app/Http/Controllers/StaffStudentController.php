<?php

namespace App\Http\Controllers;

use App\Models\PersonalDetail;
use App\Support\CentreScope;
use Illuminate\Http\Request;

class StaffStudentController extends Controller
{
    public function index(Request $request)
    {
        $searchQuery = $request->query('search');
        $studyCent = $request->query('study_cent');
        $type = $request->query('type');
        $page = (int) $request->query('page', 1);
        $perPage = min(max((int) $request->query('per_page', 10), 1), 5000);

        $query = PersonalDetail::query()->with('bioRegistration');

        CentreScope::applyStudents($query, $request->user(), $studyCent);

        if (! empty($searchQuery)) {
            $query->where(function ($q) use ($searchQuery) {
                $q->where('matric_number', 'LIKE', "%{$searchQuery}%")
                    ->orWhere('application_number', 'LIKE', "%{$searchQuery}%")
                    ->orWhere('surname', 'LIKE', "%{$searchQuery}%")
                    ->orWhere('other_names', 'LIKE', "%{$searchQuery}%");

                $names = explode(' ', $searchQuery);
                if (count($names) > 1) {
                    foreach ($names as $name) {
                        if (strlen($name) > 2) {
                            $q->orWhere('surname', 'LIKE', "%{$name}%")
                                ->orWhere('other_names', 'LIKE', "%{$name}%");
                        }
                    }
                }
            });
        }

        if ($type === 'admitted') {
            $query->where('has_admission', 1)->whereNull('matric_number');
        } elseif ($type === 'pending') {
            $query->where(function ($q) {
                $q->where('has_admission', 0)->orWhereNull('has_admission');
            })->whereNull('matric_number');
        }

        $query->orderBy('updated_at', 'desc');
        $personalDetails = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data' => $personalDetails->items(),
            'current_page' => $personalDetails->currentPage(),
            'per_page' => $personalDetails->perPage(),
            'total' => $personalDetails->total(),
        ]);
    }

    public function summary(Request $request)
    {
        $query = PersonalDetail::query();
        CentreScope::applyStudents($query, $request->user(), $request->query('study_cent'));

        $rows = (clone $query)->get([
            'id',
            'has_admission',
            'matric_number',
            'has_paid',
            'course_paid',
            'desired_study_cent',
        ]);

        $centres = CentreScope::catalogue();
        $centerStats = array_map(function ($center) use ($rows) {
            $centerStudents = $rows->filter(
                fn ($student) => CentreScope::matches($student->desired_study_cent, $center)
            );

            return [
                'name' => $center,
                'total' => $centerStudents->count(),
                'approved' => $centerStudents->filter(fn ($s) => (bool) $s->has_admission)->count(),
            ];
        }, $centres);

        return response()->json([
            'total' => $rows->count(),
            'no_admission' => $rows->filter(fn ($s) => ! $s->has_admission)->count(),
            'with_matric' => $rows->filter(fn ($s) => filled($s->matric_number))->count(),
            'approved_without_matric' => $rows->filter(
                fn ($s) => $s->has_admission && ! filled($s->matric_number)
            )->count(),
            'with_admission' => $rows->filter(fn ($s) => (bool) $s->has_admission)->count(),
            'partial_payment' => $rows->filter(
                fn ($s) => (string) $s->has_paid === '1' && (string) $s->course_paid === '0'
            )->count(),
            'full_payment' => $rows->filter(fn ($s) => (string) $s->course_paid === '1')->count(),
            'not_paid_with_matric' => $rows->filter(
                fn ($s) => filled($s->matric_number) && (string) $s->has_paid === '0'
            )->count(),
            'centres' => $centerStats,
        ]);
    }
}
