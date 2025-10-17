<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ApiMetrics
{
    /**
     * Collect detailed API metrics and performance data.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip metrics collection in testing environment  
        if (app()->environment('testing')) {
            return $next($request);
        }
        
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);
        
        // Generate unique request ID for tracing
        $requestId = $this->generateRequestId();
        $request->headers->set('X-Request-ID', $requestId);
        
        // Log request start
        Log::channel('api-metrics')->info('API Request Started', [
            'request_id' => $requestId,
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'path' => $request->path(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'content_length' => $request->header('Content-Length', 0),
            'timestamp' => now()->toISOString(),
        ]);

        $response = $next($request);

        // Calculate metrics
        $executionTime = microtime(true) - $startTime;
        $memoryUsage = memory_get_usage(true) - $startMemory;
        $peakMemory = memory_get_peak_usage(true);
        
        // Collect response metrics
        $metrics = [
            'request_id' => $requestId,
            'method' => $request->method(),
            'path' => $request->path(),
            'status_code' => $response->getStatusCode(),
            'execution_time_ms' => round($executionTime * 1000, 2),
            'memory_usage_bytes' => $memoryUsage,
            'peak_memory_bytes' => $peakMemory,
            'response_size_bytes' => strlen($response->getContent()),
            'ip' => $request->ip(),
            'timestamp' => now()->toISOString(),
        ];

        // Store metrics for aggregation
        $this->storeMetrics($metrics);
        
        // Log request completion
        Log::channel('api-metrics')->info('API Request Completed', $metrics);
        
        // Add performance headers
        $response->headers->set('X-Request-ID', $requestId);
        $response->headers->set('X-Response-Time', $metrics['execution_time_ms'] . 'ms');
        $response->headers->set('X-Memory-Usage', $this->formatBytes($memoryUsage));
        
        return $response;
    }

    /**
     * Generate unique request ID for tracing.
     */
    private function generateRequestId(): string
    {
        return 'req_' . bin2hex(random_bytes(8)) . '_' . time();
    }

    /**
     * Store metrics for aggregation and analysis.
     */
    private function storeMetrics(array $metrics): void
    {
        try {
            // Store in cache for real-time access
            $cacheKey = 'api_metrics:' . date('Y-m-d-H');
            $cachedMetrics = Cache::get($cacheKey, []);
            $cachedMetrics[] = $metrics;
            
            // Keep only last 1000 requests per hour
            if (count($cachedMetrics) > 1000) {
                $cachedMetrics = array_slice($cachedMetrics, -1000);
            }
            
            Cache::put($cacheKey, $cachedMetrics, 3600); // Store for 1 hour
            
            // Update aggregated stats
            $this->updateAggregatedStats($metrics);
            
        } catch (\Exception $e) {
            Log::warning('Failed to store API metrics', [
                'error' => $e->getMessage(),
                'metrics' => $metrics
            ]);
        }
    }

    /**
     * Update aggregated statistics.
     */
    private function updateAggregatedStats(array $metrics): void
    {
        $statsKey = 'api_stats:aggregated';
        $stats = Cache::get($statsKey, [
            'total_requests' => 0,
            'total_errors' => 0,
            'avg_response_time' => 0,
            'max_response_time' => 0,
            'min_response_time' => PHP_FLOAT_MAX,
            'status_codes' => [],
            'endpoints' => [],
            'last_updated' => now()->toISOString(),
        ]);

        // Update counters
        $stats['total_requests']++;
        if ($metrics['status_code'] >= 400) {
            $stats['total_errors']++;
        }

        // Update response time stats
        $responseTime = $metrics['execution_time_ms'];
        $stats['max_response_time'] = max($stats['max_response_time'], $responseTime);
        $stats['min_response_time'] = min($stats['min_response_time'], $responseTime);
        
        // Calculate rolling average (simple approximation)
        $stats['avg_response_time'] = (
            ($stats['avg_response_time'] * ($stats['total_requests'] - 1)) + $responseTime
        ) / $stats['total_requests'];

        // Track status codes
        $statusCode = (string)$metrics['status_code'];
        $stats['status_codes'][$statusCode] = ($stats['status_codes'][$statusCode] ?? 0) + 1;

        // Track endpoints
        $endpoint = $metrics['method'] . ' ' . $metrics['path'];
        $stats['endpoints'][$endpoint] = ($stats['endpoints'][$endpoint] ?? 0) + 1;

        $stats['last_updated'] = now()->toISOString();

        Cache::put($statsKey, $stats, 86400); // Store for 24 hours
    }

    /**
     * Format bytes to human readable format.
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $factor = floor((strlen($bytes) - 1) / 3);
        
        return sprintf("%.2f %s", $bytes / pow(1024, $factor), $units[$factor]);
    }
}