<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class ApiResponseCache
{
    /**
     * Handle an incoming request with intelligent caching.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip caching in testing environment
        if (app()->environment('testing')) {
            return $next($request);
        }
        
        // Only cache GET requests
        if ($request->method() !== 'GET') {
            return $next($request);
        }

        // Generate cache key based on request
        $cacheKey = $this->generateCacheKey($request);
        
        // Check if response is cached
        if (Cache::has($cacheKey)) {
            $cachedResponse = Cache::get($cacheKey);
            
            return response($cachedResponse['content'])
                ->setStatusCode($cachedResponse['status'])
                ->withHeaders($cachedResponse['headers'])
                ->header('X-Cache-Status', 'HIT')
                ->header('X-Cache-Key', $cacheKey);
        }

        $response = $next($request);

        // Cache successful responses
        if ($response->getStatusCode() === 200) {
            $ttl = $this->getCacheTtl($request);
            
            Cache::put($cacheKey, [
                'content' => $response->getContent(),
                'status' => $response->getStatusCode(),
                'headers' => $response->headers->all(),
            ], $ttl);
            
            $response->header('X-Cache-Status', 'MISS')
                ->header('X-Cache-TTL', $ttl)
                ->header('X-Cache-Key', $cacheKey);
        }

        return $response;
    }

    /**
     * Generate cache key from request.
     */
    private function generateCacheKey(Request $request): string
    {
        $path = $request->path();
        $query = $request->query();
        
        // Sort query parameters for consistent caching
        ksort($query);
        
        return 'api_cache:' . md5($path . serialize($query));
    }

    /**
     * Determine cache TTL based on endpoint.
     */
    private function getCacheTtl(Request $request): int
    {
        $path = $request->path();
        
        return match (true) {
            str_contains($path, 'codelists') => 3600,      // 1 hour - codelists rarely change
            str_contains($path, 'salesmen') => 300,        // 5 minutes - salesmen data
            str_contains($path, 'health') => 60,           // 1 minute - health checks
            default => 600,                                // 10 minutes - default
        };
    }
}