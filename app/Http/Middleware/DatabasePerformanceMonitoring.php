<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class DatabasePerformanceMonitoring
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Skip monitoring in testing environment
        if (app()->environment('testing')) {
            return $next($request);
        }
        
        $startTime = microtime(true);
        $startQueries = $this->getQueryCount();
        
        // Enable query logging for this request
        DB::enableQueryLog();
        
        $response = $next($request);
        
        $endTime = microtime(true);
        $endQueries = $this->getQueryCount();
        
        $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds
        $queryCount = $endQueries - $startQueries;
        $queries = DB::getQueryLog();
        
        // Calculate total query time
        $totalQueryTime = 0;
        $slowQueries = [];
        
        foreach ($queries as $query) {
            $queryTime = $query['time'] ?? 0;
            $totalQueryTime += $queryTime;
            
            // Flag slow queries (> 100ms)
            if ($queryTime > 100) {
                $slowQueries[] = [
                    'sql' => $query['query'],
                    'time' => $queryTime,
                    'bindings' => $query['bindings']
                ];
            }
        }
        
        // Store performance metrics
        $metrics = [
            'request_id' => $request->header('X-Request-ID', uniqid()),
            'method' => $request->method(),
            'uri' => $request->getRequestUri(),
            'execution_time_ms' => round($executionTime, 2),
            'query_count' => $queryCount,
            'total_query_time_ms' => round($totalQueryTime, 2),
            'slow_queries_count' => count($slowQueries),
            'memory_usage_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
            'timestamp' => now()->toISOString()
        ];
        
        // Log performance data
        Log::channel('db-performance')->info('Database performance metrics', $metrics);
        
        // Cache aggregated statistics
        $this->updateAggregatedStats($metrics);
        
        // Log slow queries separately if any
        if (!empty($slowQueries)) {
            Log::channel('db-performance')->warning('Slow queries detected', [
                'request_id' => $metrics['request_id'],
                'slow_queries' => $slowQueries
            ]);
        }
        
        // Add performance headers to response
        $response->headers->set('X-DB-Query-Count', $queryCount);
        $response->headers->set('X-DB-Query-Time', round($totalQueryTime, 2));
        $response->headers->set('X-Execution-Time', round($executionTime, 2));
        
        // Disable query logging to prevent memory buildup
        DB::disableQueryLog();
        
        return $response;
    }
    
    /**
     * Get current query count from database statistics.
     */
    private function getQueryCount(): int
    {
        try {
            // Get total queries executed in current session
            $result = DB::select("
                SELECT sum(calls) as total_calls 
                FROM pg_stat_statements 
                WHERE userid = (SELECT usesysid FROM pg_user WHERE usename = current_user)
            ");
            
            return $result[0]->total_calls ?? 0;
        } catch (\Exception $e) {
            // Fallback if pg_stat_statements is not available
            return 0;
        }
    }
    
    /**
     * Update aggregated performance statistics.
     */
    private function updateAggregatedStats(array $metrics): void
    {
        $cacheKey = 'db_performance_stats_' . now()->format('Y-m-d-H');
        
        try {
            $stats = Cache::get($cacheKey, [
                'total_requests' => 0,
                'total_execution_time' => 0,
                'total_queries' => 0,
                'total_query_time' => 0,
                'slow_queries_count' => 0,
                'peak_memory_usage' => 0,
                'hourly_breakdown' => []
            ]);
            
            // Update aggregated metrics
            $stats['total_requests']++;
            $stats['total_execution_time'] += $metrics['execution_time_ms'];
            $stats['total_queries'] += $metrics['query_count'];
            $stats['total_query_time'] += $metrics['total_query_time_ms'];
            $stats['slow_queries_count'] += $metrics['slow_queries_count'];
            $stats['peak_memory_usage'] = max($stats['peak_memory_usage'], $metrics['memory_usage_mb']);
            
            // Store hourly breakdown
            $minute = now()->format('H:i');
            if (!isset($stats['hourly_breakdown'][$minute])) {
                $stats['hourly_breakdown'][$minute] = [
                    'requests' => 0,
                    'avg_execution_time' => 0,
                    'avg_queries' => 0
                ];
            }
            
            $stats['hourly_breakdown'][$minute]['requests']++;
            $stats['hourly_breakdown'][$minute]['avg_execution_time'] = 
                ($stats['hourly_breakdown'][$minute]['avg_execution_time'] + $metrics['execution_time_ms']) / 2;
            $stats['hourly_breakdown'][$minute]['avg_queries'] = 
                ($stats['hourly_breakdown'][$minute]['avg_queries'] + $metrics['query_count']) / 2;
            
            // Cache for 2 hours
            Cache::put($cacheKey, $stats, now()->addHours(2));
            
        } catch (\Exception $e) {
            Log::error('Failed to update aggregated DB performance stats', ['error' => $e->getMessage()]);
        }
    }
}