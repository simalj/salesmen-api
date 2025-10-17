<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use OpenApi\Attributes as OA;

#[OA\PathItem(
    path: "/api/v1/database",
    summary: "Database performance monitoring endpoints",
    description: "Endpoints for monitoring and optimizing database performance"
)]
#[OA\Tag(
    name: "Database Performance",
    description: "Database performance monitoring and optimization endpoints"
)]
class DatabasePerformanceController extends Controller
{
    #[OA\Get(
        path: "/api/v1/database/performance",
        summary: "Get database performance metrics",
        description: "Retrieve comprehensive database performance statistics and metrics",
        tags: ["Database Performance"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Database performance metrics retrieved successfully",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        "success" => new OA\Property(property: "success", type: "boolean", example: true),
                        "data" => new OA\Property(
                            property: "data",
                            type: "object",
                            properties: [
                                "database_info" => new OA\Property(
                                    property: "database_info",
                                    type: "object",
                                    properties: [
                                        "name" => new OA\Property(property: "name", type: "string", example: "salesmen_api"),
                                        "version" => new OA\Property(property: "version", type: "string", example: "PostgreSQL 16.0"),
                                        "size" => new OA\Property(property: "size", type: "string", example: "45 MB"),
                                        "uptime" => new OA\Property(property: "uptime", type: "string", example: "2 days 14:23:45")
                                    ]
                                ),
                                "connection_stats" => new OA\Property(
                                    property: "connection_stats",
                                    type: "object",
                                    properties: [
                                        "active_connections" => new OA\Property(property: "active_connections", type: "integer", example: 5),
                                        "max_connections" => new OA\Property(property: "max_connections", type: "integer", example: 100),
                                        "connection_utilization" => new OA\Property(property: "connection_utilization", type: "number", format: "float", example: 5.0)
                                    ]
                                ),
                                "query_stats" => new OA\Property(
                                    property: "query_stats",
                                    type: "object",
                                    properties: [
                                        "total_queries" => new OA\Property(property: "total_queries", type: "integer", example: 1250),
                                        "queries_per_second" => new OA\Property(property: "queries_per_second", type: "number", format: "float", example: 12.5),
                                        "avg_query_time_ms" => new OA\Property(property: "avg_query_time_ms", type: "number", format: "float", example: 15.3),
                                        "slow_queries_count" => new OA\Property(property: "slow_queries_count", type: "integer", example: 3)
                                    ]
                                ),
                                "table_stats" => new OA\Property(
                                    property: "table_stats",
                                    type: "array",
                                    items: new OA\Items(
                                        type: "object",
                                        properties: [
                                            "table_name" => new OA\Property(property: "table_name", type: "string", example: "salesmen"),
                                            "size" => new OA\Property(property: "size", type: "string", example: "12 MB"),
                                            "row_count" => new OA\Property(property: "row_count", type: "integer", example: 1500),
                                            "total_operations" => new OA\Property(property: "total_operations", type: "integer", example: 2300)
                                        ]
                                    )
                                )
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 500,
                description: "Internal server error",
                content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")
            )
        ]
    )]
    public function getPerformanceMetrics(): JsonResponse
    {
        try {
            $metrics = [
                'database_info' => $this->getDatabaseInfo(),
                'connection_stats' => $this->getConnectionStats(),
                'query_stats' => $this->getQueryStats(),
                'table_stats' => $this->getTableStats(),
                'performance_summary' => $this->getPerformanceSummary()
            ];

            return response()->json([
                'success' => true,
                'data' => $metrics,
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to retrieve database performance metrics', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve performance metrics',
                'message' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    #[OA\Get(
        path: "/api/v1/database/slow-queries",
        summary: "Get slow query analysis",
        description: "Retrieve analysis of slow-performing database queries",
        tags: ["Database Performance"],
        parameters: [
            new OA\Parameter(
                name: "limit",
                description: "Number of slow queries to return",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "integer", example: 10)
            ),
            new OA\Parameter(
                name: "min_time",
                description: "Minimum query time in milliseconds",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "number", format: "float", example: 100.0)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Slow queries analysis retrieved successfully",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        "success" => new OA\Property(property: "success", type: "boolean", example: true),
                        "data" => new OA\Property(
                            property: "data",
                            type: "object",
                            properties: [
                                "slow_queries" => new OA\Property(
                                    property: "slow_queries",
                                    type: "array",
                                    items: new OA\Items(
                                        type: "object",
                                        properties: [
                                            "query" => new OA\Property(property: "query", type: "string", example: "SELECT * FROM salesmen WHERE..."),
                                            "calls" => new OA\Property(property: "calls", type: "integer", example: 45),
                                            "total_time_ms" => new OA\Property(property: "total_time_ms", type: "number", format: "float", example: 1250.5),
                                            "avg_time_ms" => new OA\Property(property: "avg_time_ms", type: "number", format: "float", example: 27.8),
                                            "percentage_total_time" => new OA\Property(property: "percentage_total_time", type: "number", format: "float", example: 15.2)
                                        ]
                                    )
                                ),
                                "summary" => new OA\Property(
                                    property: "summary",
                                    type: "object",
                                    properties: [
                                        "total_slow_queries" => new OA\Property(property: "total_slow_queries", type: "integer", example: 12),
                                        "total_time_slow_queries_ms" => new OA\Property(property: "total_time_slow_queries_ms", type: "number", format: "float", example: 5432.1)
                                    ]
                                )
                            ]
                        )
                    ]
                )
            )
        ]
    )]
    public function getSlowQueries(Request $request): JsonResponse
    {
        $limit = $request->query('limit', 10);
        $minTime = $request->query('min_time', 100.0);

        try {
            $slowQueries = $this->analyzeSlowQueries($limit, $minTime);

            return response()->json([
                'success' => true,
                'data' => $slowQueries,
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to retrieve slow queries analysis', [
                'error' => $e->getMessage(),
                'limit' => $limit,
                'min_time' => $minTime
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to analyze slow queries',
                'message' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    #[OA\Post(
        path: "/api/v1/database/optimize",
        summary: "Optimize database performance",
        description: "Run database optimization procedures like VACUUM, ANALYZE, and reindex",
        tags: ["Database Performance"],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\JsonContent(
                type: "object",
                properties: [
                    "operations" => new OA\Property(
                        property: "operations",
                        type: "array",
                        items: new OA\Items(type: "string", enum: ["vacuum", "analyze", "reindex"]),
                        example: ["vacuum", "analyze"]
                    ),
                    "tables" => new OA\Property(
                        property: "tables",
                        type: "array",
                        items: new OA\Items(type: "string"),
                        example: ["salesmen", "codelists"]
                    )
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Database optimization completed successfully",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        "success" => new OA\Property(property: "success", type: "boolean", example: true),
                        "data" => new OA\Property(
                            property: "data",
                            type: "object",
                            properties: [
                                "operations_performed" => new OA\Property(
                                    property: "operations_performed",
                                    type: "array",
                                    items: new OA\Items(type: "string"),
                                    example: ["VACUUM", "ANALYZE"]
                                ),
                                "tables_optimized" => new OA\Property(
                                    property: "tables_optimized",
                                    type: "array",
                                    items: new OA\Items(type: "string"),
                                    example: ["salesmen", "codelists"]
                                ),
                                "execution_time_ms" => new OA\Property(property: "execution_time_ms", type: "number", format: "float", example: 1250.5)
                            ]
                        )
                    ]
                )
            )
        ]
    )]
    public function optimizeDatabase(Request $request): JsonResponse
    {
        $operations = $request->input('operations', ['analyze']);
        $tables = $request->input('tables', []);

        $startTime = microtime(true);
        $performedOperations = [];

        try {
            foreach ($operations as $operation) {
                switch (strtolower($operation)) {
                    case 'vacuum':
                        $this->performVacuum($tables);
                        $performedOperations[] = 'VACUUM';
                        break;
                    case 'analyze':
                        $this->performAnalyze($tables);
                        $performedOperations[] = 'ANALYZE';
                        break;
                    case 'reindex':
                        $this->performReindex($tables);
                        $performedOperations[] = 'REINDEX';
                        break;
                }
            }

            $executionTime = (microtime(true) - $startTime) * 1000;

            Log::info('Database optimization completed', [
                'operations' => $performedOperations,
                'tables' => $tables,
                'execution_time_ms' => $executionTime
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'operations_performed' => $performedOperations,
                    'tables_optimized' => $tables,
                    'execution_time_ms' => round($executionTime, 2)
                ],
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            Log::error('Database optimization failed', [
                'error' => $e->getMessage(),
                'operations' => $operations,
                'tables' => $tables
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Database optimization failed',
                'message' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get basic database information.
     */
    private function getDatabaseInfo(): array
    {
        $dbInfo = DB::select('SELECT version() as version, current_database() as name')[0];
        $dbSize = DB::select("SELECT pg_size_pretty(pg_database_size(current_database())) as size")[0];
        
        // Get database uptime
        $uptime = DB::select("SELECT date_trunc('second', current_timestamp - pg_postmaster_start_time()) as uptime")[0];

        return [
            'name' => $dbInfo->name,
            'version' => $dbInfo->version,
            'size' => $dbSize->size,
            'uptime' => $uptime->uptime
        ];
    }

    /**
     * Get connection statistics.
     */
    private function getConnectionStats(): array
    {
        $activeConnections = DB::select("SELECT count(*) as count FROM pg_stat_activity WHERE state = 'active'")[0];
        $maxConnections = DB::select("SHOW max_connections")[0];

        $connectionUtilization = ($activeConnections->count / $maxConnections->max_connections) * 100;

        return [
            'active_connections' => (int) $activeConnections->count,
            'max_connections' => (int) $maxConnections->max_connections,
            'connection_utilization' => round($connectionUtilization, 2)
        ];
    }

    /**
     * Get query statistics.
     */
    private function getQueryStats(): array
    {
        try {
            // Check if pg_stat_statements is available
            $extensionCheck = DB::select("SELECT 1 FROM pg_extension WHERE extname = 'pg_stat_statements'");
            
            if (empty($extensionCheck)) {
                return [
                    'total_queries' => 0,
                    'queries_per_second' => 0,
                    'avg_query_time_ms' => 0,
                    'slow_queries_count' => 0,
                    'note' => 'pg_stat_statements extension not available'
                ];
            }

            $stats = DB::select("
                SELECT 
                    sum(calls) as total_queries,
                    round(avg(mean_exec_time)::numeric, 2) as avg_query_time,
                    count(*) filter (where mean_exec_time > 100) as slow_queries_count
                FROM pg_stat_statements
            ")[0];

            return [
                'total_queries' => (int) ($stats->total_queries ?? 0),
                'queries_per_second' => 0, // Would need time-based calculation
                'avg_query_time_ms' => (float) ($stats->avg_query_time ?? 0),
                'slow_queries_count' => (int) ($stats->slow_queries_count ?? 0)
            ];

        } catch (\Exception $e) {
            return [
                'total_queries' => 0,
                'queries_per_second' => 0,
                'avg_query_time_ms' => 0,
                'slow_queries_count' => 0,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get table statistics.
     */
    private function getTableStats(): array
    {
        $tables = DB::select("
            SELECT 
                t.relname as tablename,
                pg_size_pretty(pg_total_relation_size(t.schemaname||'.'||t.relname)) as size,
                COALESCE(t.n_tup_ins + t.n_tup_upd + t.n_tup_del, 0) as total_operations,
                (SELECT count(*) FROM information_schema.columns WHERE table_name = t.relname AND table_schema = 'public') as column_count
            FROM pg_stat_user_tables t
            ORDER BY pg_total_relation_size(t.schemaname||'.'||t.relname) DESC
            LIMIT 10
        ");

        return array_map(function ($table) {
            return [
                'table_name' => $table->tablename,
                'size' => $table->size,
                'total_operations' => (int) $table->total_operations,
                'column_count' => (int) $table->column_count
            ];
        }, $tables);
    }

    /**
     * Get performance summary from cached metrics.
     */
    private function getPerformanceSummary(): array
    {
        $cacheKey = 'db_performance_stats_' . now()->format('Y-m-d-H');
        $stats = Cache::get($cacheKey, []);

        return [
            'requests_this_hour' => $stats['total_requests'] ?? 0,
            'avg_execution_time_ms' => isset($stats['total_requests'], $stats['total_execution_time']) && $stats['total_requests'] > 0
                ? round($stats['total_execution_time'] / $stats['total_requests'], 2)
                : 0,
            'avg_queries_per_request' => isset($stats['total_requests'], $stats['total_queries']) && $stats['total_requests'] > 0
                ? round($stats['total_queries'] / $stats['total_requests'], 2)
                : 0,
            'peak_memory_usage_mb' => $stats['peak_memory_usage'] ?? 0
        ];
    }

    /**
     * Analyze slow queries.
     */
    private function analyzeSlowQueries(int $limit, float $minTime): array
    {
        try {
            $extensionCheck = DB::select("SELECT 1 FROM pg_extension WHERE extname = 'pg_stat_statements'");
            
            if (empty($extensionCheck)) {
                return [
                    'slow_queries' => [],
                    'summary' => [
                        'total_slow_queries' => 0,
                        'total_time_slow_queries_ms' => 0
                    ],
                    'note' => 'pg_stat_statements extension not available'
                ];
            }

            $slowQueries = DB::select("
                SELECT 
                    substring(query, 1, 100) as query,
                    calls,
                    round(total_exec_time::numeric, 2) as total_time_ms,
                    round(mean_exec_time::numeric, 2) as avg_time_ms,
                    round((100 * total_exec_time / sum(total_exec_time) OVER())::numeric, 2) as percentage_total_time
                FROM pg_stat_statements
                WHERE mean_exec_time > ? AND calls > 1
                ORDER BY mean_exec_time DESC
                LIMIT ?
            ", [$minTime, $limit]);

            $summary = [
                'total_slow_queries' => count($slowQueries),
                'total_time_slow_queries_ms' => array_sum(array_column($slowQueries, 'total_time_ms'))
            ];

            return [
                'slow_queries' => $slowQueries,
                'summary' => $summary
            ];

        } catch (\Exception $e) {
            throw new \Exception("Failed to analyze slow queries: " . $e->getMessage());
        }
    }

    /**
     * Perform VACUUM operation.
     */
    private function performVacuum(array $tables): void
    {
        if (empty($tables)) {
            DB::statement('VACUUM');
        } else {
            foreach ($tables as $table) {
                DB::statement("VACUUM {$table}");
            }
        }
    }

    /**
     * Perform ANALYZE operation.
     */
    private function performAnalyze(array $tables): void
    {
        if (empty($tables)) {
            DB::statement('ANALYZE');
        } else {
            foreach ($tables as $table) {
                DB::statement("ANALYZE {$table}");
            }
        }
    }

    /**
     * Perform REINDEX operation.
     */
    private function performReindex(array $tables): void
    {
        if (empty($tables)) {
            // Get all user tables
            $userTables = DB::select("
                SELECT relname as tablename 
                FROM pg_stat_user_tables
            ");
            
            foreach ($userTables as $table) {
                DB::statement("REINDEX TABLE {$table->tablename}");
            }
        } else {
            foreach ($tables as $table) {
                DB::statement("REINDEX TABLE {$table}");
            }
        }
    }
}