<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

#[OA\PathItem(
    path: "/api/v1/metrics",
    summary: "API metrics and monitoring endpoints",
    description: "Endpoints for retrieving API performance metrics and monitoring data"
)]
#[OA\Tag(
    name: "Metrics", 
    description: "API performance metrics and monitoring endpoints"
)]
class MetricsController extends Controller
{
    /**
     * Get API performance metrics.
     */
    #[OA\Get(
        path: "/api/metrics/performance",
        operationId: "getPerformanceMetrics",
        summary: "Get API performance metrics",
        description: "Retrieve detailed performance statistics and metrics",
        tags: ["Metrics & Monitoring"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Performance metrics retrieved successfully",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(property: "total_requests", type: "integer", example: 15420),
                        new OA\Property(property: "total_errors", type: "integer", example: 245),
                        new OA\Property(property: "error_rate", type: "string", example: "1.59%"),
                        new OA\Property(property: "avg_response_time", type: "number", example: 125.45),
                        new OA\Property(property: "max_response_time", type: "number", example: 2340.12),
                        new OA\Property(property: "min_response_time", type: "number", example: 12.34),
                        new OA\Property(property: "status_codes", type: "object", example: ["200" => 14580, "404" => 180, "422" => 65]),
                        new OA\Property(property: "top_endpoints", type: "object", example: ["GET /api/v1/salesmen" => 8520, "POST /api/v1/salesmen" => 1240])
                    ]
                )
            )
        ]
    )]
    public function performance(): JsonResponse
    {
        $stats = Cache::get('api_stats:aggregated', [
            'total_requests' => 0,
            'total_errors' => 0,
            'avg_response_time' => 0,
            'max_response_time' => 0,
            'min_response_time' => 0,
            'status_codes' => [],
            'endpoints' => [],
        ]);

        // Calculate error rate
        $errorRate = $stats['total_requests'] > 0 
            ? round(($stats['total_errors'] / $stats['total_requests']) * 100, 2) . '%'
            : '0%';

        // Get top endpoints
        arsort($stats['endpoints']);
        $topEndpoints = array_slice($stats['endpoints'], 0, 10, true);

        return response()->json([
            'performance_metrics' => [
                'total_requests' => $stats['total_requests'],
                'total_errors' => $stats['total_errors'],
                'error_rate' => $errorRate,
                'avg_response_time' => round($stats['avg_response_time'], 2),
                'max_response_time' => $stats['max_response_time'],
                'min_response_time' => $stats['min_response_time'] === PHP_FLOAT_MAX ? 0 : $stats['min_response_time'],
                'status_codes' => $stats['status_codes'],
                'top_endpoints' => $topEndpoints,
                'last_updated' => $stats['last_updated'] ?? null,
            ],
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Get recent API requests for tracing.
     */
    #[OA\Get(
        path: "/api/metrics/requests",
        operationId: "getRecentRequests",
        summary: "Get recent API requests",
        description: "Retrieve recent API requests for debugging and tracing",
        tags: ["Metrics & Monitoring"],
        parameters: [
            new OA\Parameter(
                name: "limit",
                description: "Number of requests to return",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "integer", minimum: 1, maximum: 100, default: 50)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Recent requests retrieved successfully",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(
                            property: "requests",
                            type: "array",
                            items: new OA\Items(
                                type: "object",
                                properties: [
                                    new OA\Property(property: "request_id", type: "string"),
                                    new OA\Property(property: "method", type: "string"),
                                    new OA\Property(property: "path", type: "string"),
                                    new OA\Property(property: "status_code", type: "integer"),
                                    new OA\Property(property: "execution_time_ms", type: "number"),
                                    new OA\Property(property: "timestamp", type: "string", format: "date-time")
                                ]
                            )
                        ),
                        new OA\Property(property: "total_count", type: "integer"),
                        new OA\Property(property: "timestamp", type: "string", format: "date-time")
                    ]
                )
            )
        ]
    )]
    public function requests(Request $request): JsonResponse
    {
        $limit = $request->integer('limit', 50);
        $limit = min(max($limit, 1), 100); // Ensure between 1 and 100

        // Get recent requests from current hour
        $currentHourKey = 'api_metrics:' . date('Y-m-d-H');
        $recentRequests = Cache::get($currentHourKey, []);

        // Also check previous hour for more data if needed
        if (count($recentRequests) < $limit) {
            $prevHourKey = 'api_metrics:' . date('Y-m-d-H', strtotime('-1 hour'));
            $prevHourRequests = Cache::get($prevHourKey, []);
            $recentRequests = array_merge($prevHourRequests, $recentRequests);
        }

        // Sort by timestamp (newest first) and limit results
        usort($recentRequests, function ($a, $b) {
            return strcmp($b['timestamp'], $a['timestamp']);
        });
        
        $limitedRequests = array_slice($recentRequests, 0, $limit);

        return response()->json([
            'requests' => $limitedRequests,
            'total_count' => count($recentRequests),
            'limit' => $limit,
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Get database performance metrics.
     */
    #[OA\Get(
        path: "/api/metrics/database",
        operationId: "getDatabaseMetrics",
        summary: "Get database performance metrics",
        description: "Retrieve database connection and query performance statistics",
        tags: ["Metrics & Monitoring"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Database metrics retrieved successfully",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(property: "connection_status", type: "string", example: "connected"),
                        new OA\Property(property: "total_queries", type: "integer", example: 42),
                        new OA\Property(property: "slow_queries", type: "integer", example: 3),
                        new OA\Property(property: "avg_query_time", type: "number", example: 15.23),
                        new OA\Property(property: "database_size", type: "string", example: "2.4 MB")
                    ]
                )
            )
        ]
    )]
    public function database(): JsonResponse
    {
        try {
            // Test database connection
            $connectionTest = DB::select('SELECT 1 as test');
            $connectionStatus = !empty($connectionTest) ? 'connected' : 'disconnected';

            // Get query log stats (if enabled)
            $queryCount = count(DB::getQueryLog());

            // Get database size (PostgreSQL specific)
            $dbSize = 'unknown';
            try {
                $sizeQuery = DB::select("
                    SELECT pg_size_pretty(pg_database_size(current_database())) as size
                ");
                $dbSize = $sizeQuery[0]->size ?? 'unknown';
            } catch (\Exception $e) {
                // Fallback for non-PostgreSQL or insufficient permissions
            }

            // Get table statistics
            $tableStats = [];
            try {
                $tables = DB::select("
                    SELECT 
                        schemaname,
                        tablename,
                        n_tup_ins as inserts,
                        n_tup_upd as updates,
                        n_tup_del as deletes
                    FROM pg_stat_user_tables
                    ORDER BY (n_tup_ins + n_tup_upd + n_tup_del) DESC
                    LIMIT 5
                ");
                $tableStats = $tables;
            } catch (\Exception $e) {
                // Fallback for insufficient permissions
            }

            return response()->json([
                'database_metrics' => [
                    'connection_status' => $connectionStatus,
                    'total_queries' => $queryCount,
                    'database_size' => $dbSize,
                    'table_statistics' => $tableStats,
                    'connection_info' => [
                        'driver' => config('database.default'),
                        'host' => config('database.connections.pgsql.host'),
                        'database' => config('database.connections.pgsql.database'),
                    ]
                ],
                'timestamp' => now()->toISOString(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'database_metrics' => [
                    'connection_status' => 'error',
                    'error' => $e->getMessage(),
                ],
                'timestamp' => now()->toISOString(),
            ], 503);
        }
    }
}