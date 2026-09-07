<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $query = AuditLog::query()->with('actor')->orderByDesc('created_at');

        if ($action = $request->query('action')) {
            $query->where('action', $action);
        }

        if ($actorId = $request->query('actor_id')) {
            $query->where('actor_id', $actorId);
        }

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhere('actor_name', 'like', "%{$search}%");
            });
        }

        if ($from = $request->query('from')) {
            $query->whereDate('created_at', '>=', $from);
        }

        if ($to = $request->query('to')) {
            $query->whereDate('created_at', '<=', $to);
        }

        return response()->json($query->paginate($request->integer('per_page', 30)));
    }

    /**
     * Distinct action values seen so far, for a filter dropdown.
     */
    public function actions()
    {
        $actions = AuditLog::query()
            ->select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action');

        return response()->json(['data' => $actions]);
    }
}
