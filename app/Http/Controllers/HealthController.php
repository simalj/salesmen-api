<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use OpenApi\Attributes as OA;

#[OA\PathItem(
    path: "/api/health",
    summary: "Health check endpoint",
    description: "Application health status endpoint"
)]
#[OA\Tag(
    name: "Health",
    description: "Application health monitoring"
)]
class HealthController extends Controller
{
    /**
     * API Health Check endpoint.
     * Checks database connection, cache, and basic system status.
     */
    #[OA\Get(
        path: "/api/health",
        operationId: "healthCheck",
        summary: "Application health check",
        description: "Returns the current health status of the application including database and cache connectivity",
        tags: ["Health Check"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Application is healthy",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(property: "status", type: "string", example: "healthy"),
                        new OA\Property(property: "timestamp", type: "string", format: "date-time"),
                        new OA\Property(
                            property: "services",
                            type: "object",
                            properties: [
                                new OA\Property(property: "database", type: "string", example: "connected"),
                                new OA\Property(property: "cache", type: "string", example: "working")
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 503,
                description: "Application is unhealthy",
                content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")
            )
        ]
    )]
    public function check(): JsonResponse
    {
        $status = 'healthy';
        $checks = [];
        
        // Database check
        try {
            DB::connection()->getPdo();
            $checks['database'] = 'connected';
        } catch (\Exception $e) {
            $checks['database'] = 'failed';
            $status = 'unhealthy';
        }
        
        // Cache check
        try {
            Cache::put('health_check', 'ok', 10);
            $cached = Cache::get('health_check');
            $checks['cache'] = $cached === 'ok' ? 'working' : 'failed';
        } catch (\Exception $e) {
            $checks['cache'] = 'failed';
            $status = 'unhealthy';
        }
        
        // Codelists data check
        try {
            $genderCount = \App\Models\Gender::count();
            $checks['codelists'] = $genderCount > 0 ? 'seeded' : 'empty';
        } catch (\Exception $e) {
            $checks['codelists'] = 'failed';
            $status = 'unhealthy';
        }
        
        return response()->json([
            'status' => $status,
            'timestamp' => now()->toISOString(),
            'version' => 'v1.0.0',
            'checks' => $checks,
            'uptime' => $this->getUptime(),
        ], $status === 'healthy' ? 200 : 503);
    }
    
    /**
     * Get system uptime.
     */
    private function getUptime(): string
    {
        if (function_exists('sys_getloadavg')) {
            $uptime = shell_exec('uptime -p') ?: 'unknown';
            return trim($uptime);
        }
        
        return 'not available';
    }
}