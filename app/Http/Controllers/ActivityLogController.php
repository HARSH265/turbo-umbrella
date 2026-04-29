<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
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
        $this->applySocietyScope($query);

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

        $modulesQuery = ActivityLog::query();
        $actionsQuery = ActivityLog::query();
        $this->applySocietyScope($modulesQuery);
        $this->applySocietyScope($actionsQuery);

        $modules = $modulesQuery->distinct()->pluck('module');
        $actions = $actionsQuery->distinct()->pluck('action');

        return view('activity-logs.index', compact('logs', 'modules', 'actions'));
    }

    public function show(string $module, int $entityId)
    {
        $this->authorizeEntityAccess($module, $entityId);

        $logs = ActivityLog::forEntity($module, $entityId)
            ->with('user')
            ->tap(function (Builder $query) {
                $this->applySocietyScope($query);
            })
            ->latest()
            ->paginate(20);

        return view('activity-logs.show', compact('logs', 'module', 'entityId'));
    }

    private function applySocietyScope(Builder $query): void
    {
        $user = Auth::user();

        if (!$user || !$user->isSocietyAdmin() || !$user->society_id) {
            return;
        }

        $societyId = $user->society_id;

        $query->where(function (Builder $scope) use ($societyId) {
            $scope->whereHas('user', function (Builder $userQuery) use ($societyId) {
                $userQuery->where('society_id', $societyId);
            })->orWhere(function (Builder $entityScope) use ($societyId) {
                $entityScope->where(function (Builder $q) use ($societyId) {
                    $q->where('module', 'society')
                        ->where('entity_id', $societyId);
                })->orWhere(function (Builder $q) use ($societyId) {
                    $q->where('module', 'tower')
                        ->whereIn('entity_id', function ($subQuery) use ($societyId) {
                            $subQuery->select('id')
                                ->from('towers')
                                ->where('society_id', $societyId);
                        });
                })->orWhere(function (Builder $q) use ($societyId) {
                    $q->where('module', 'flat')
                        ->whereIn('entity_id', function ($subQuery) use ($societyId) {
                            $subQuery->select('flats.id')
                                ->from('flats')
                                ->join('towers', 'towers.id', '=', 'flats.tower_id')
                                ->where('towers.society_id', $societyId);
                        });
                })->orWhere(function (Builder $q) use ($societyId) {
                    $q->where('module', 'user')
                        ->whereIn('entity_id', function ($subQuery) use ($societyId) {
                            $subQuery->select('id')
                                ->from('users')
                                ->where('society_id', $societyId);
                        });
                })->orWhere(function (Builder $q) use ($societyId) {
                    $q->where('module', 'notice')
                        ->whereIn('entity_id', function ($subQuery) use ($societyId) {
                            $subQuery->select('id')
                                ->from('notices')
                                ->where('society_id', $societyId);
                        });
                })->orWhere(function (Builder $q) use ($societyId) {
                    $q->where('module', 'complaint')
                        ->whereIn('entity_id', function ($subQuery) use ($societyId) {
                            $subQuery->select('complaints.id')
                                ->from('complaints')
                                ->join('flats', 'flats.id', '=', 'complaints.flat_id')
                                ->join('towers', 'towers.id', '=', 'flats.tower_id')
                                ->where('towers.society_id', $societyId);
                        });
                })->orWhere(function (Builder $q) use ($societyId) {
                    $q->where('module', 'maintenance')
                        ->whereIn('entity_id', function ($subQuery) use ($societyId) {
                            $subQuery->select('maintenances.id')
                                ->from('maintenances')
                                ->join('flats', 'flats.id', '=', 'maintenances.flat_id')
                                ->join('towers', 'towers.id', '=', 'flats.tower_id')
                                ->where('towers.society_id', $societyId);
                        });
                })->orWhere(function (Builder $q) use ($societyId) {
                    $q->where('module', 'maintenance_policy')
                        ->whereIn('entity_id', function ($subQuery) use ($societyId) {
                            $subQuery->select('id')
                                ->from('maintenance_policies')
                                ->where('society_id', $societyId);
                        });
                });
            });
        });
    }

    private function authorizeEntityAccess(string $module, int $entityId): void
    {
        $user = Auth::user();

        if (!$user || !$user->isSocietyAdmin() || !$user->society_id) {
            return;
        }

        $societyId = $user->society_id;

        $allowed = match ($module) {
            'society' => $entityId === (int) $societyId,
            'tower' => \App\Models\Tower::whereKey($entityId)->where('society_id', $societyId)->exists(),
            'flat' => \App\Models\Flat::whereKey($entityId)
                ->whereHas('tower', function (Builder $query) use ($societyId) {
                    $query->where('society_id', $societyId);
                })
                ->exists(),
            'user' => \App\Models\User::whereKey($entityId)->where('society_id', $societyId)->exists(),
            'notice' => \App\Models\Notice::whereKey($entityId)->where('society_id', $societyId)->exists(),
            'complaint' => \App\Models\Complaint::whereKey($entityId)
                ->whereHas('flat.tower', function (Builder $query) use ($societyId) {
                    $query->where('society_id', $societyId);
                })
                ->exists(),
            'maintenance' => \App\Models\Maintenance::whereKey($entityId)
                ->whereHas('flat.tower', function (Builder $query) use ($societyId) {
                    $query->where('society_id', $societyId);
                })
                ->exists(),
            'maintenance_policy' => \App\Models\MaintenancePolicy::whereKey($entityId)
                ->where('society_id', $societyId)
                ->exists(),
            default => false,
        };

        if (!$allowed) {
            abort(403, 'Unauthorized access to these activity logs.');
        }
    }
}
