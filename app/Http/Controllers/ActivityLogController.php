<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;

/**
 * ActivityLogController
 * 
 * Displays activity logs for auditing
 * Admin only access
 */
class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $query = ActivityLog::with('user');

        // Filter by module
        if ($request->filled('module')) {
            $query->byModule($request->module);
        }

        // Filter by action
        if ($request->filled('action')) {
            $query->byAction($request->action);
        }

        // Filter by user
        if ($request->filled('user_id')) {
            $query->byUser($request->user_id);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $logs = $query->latest()->paginate(50)->withQueryString();

        $modules = ActivityLog::distinct()->pluck('module');
        $actions = ActivityLog::distinct()->pluck('action');

        return view('activity-logs.index', compact('logs', 'modules', 'actions'));
    }

    public function show(string $module, int $entityId)
    {
        $logs = ActivityLog::forEntity($module, $entityId)
            ->with('user')
            ->latest()
            ->paginate(20);

        return view('activity-logs.show', compact('logs', 'module', 'entityId'));
    }
}
