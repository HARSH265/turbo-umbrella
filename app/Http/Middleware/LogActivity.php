<?php

namespace App\Http\Middleware;

use App\Services\ActivityLogService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogActivity
{
    protected ActivityLogService $activityLog;

    public function __construct(ActivityLogService $activityLog)
    {
        $this->activityLog = $activityLog;
    }

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (auth()->check() && $response->getStatusCode() < 400) {
            $this->logRequest($request);
        }

        return $response;
    }

    private function logRequest(Request $request): void
    {
        $routeName = $request->route()?->getName();
        if (!$routeName) {
            return;
        }

        $module = $this->extractModule($routeName);
        $action = $this->extractAction($request->method());

        if ($module && $action) {
            $this->activityLog->log($action, $module);
        }
    }

    private function extractModule(?string $routeName): ?string
    {
        if (!$routeName) {
            return null;
        }

        $parts = explode('.', $routeName);
        return $parts[0] ?? null;
    }

    private function extractAction(string $method): string
    {
        return match($method) {
            'POST' => 'create',
            'PUT', 'PATCH' => 'update',
            'DELETE' => 'delete',
            default => 'view',
        };
    }
}