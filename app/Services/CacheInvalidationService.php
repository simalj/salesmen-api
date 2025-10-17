<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CacheInvalidationService
{
    /**
     * Invalidate all cache related to salesmen.
     */
    public function invalidateSalesmenCache(): void
    {
        try {
            // Check if we're using Redis cache store
            $cacheStore = Cache::getStore();
            
            if ($cacheStore instanceof \Illuminate\Cache\RedisStore) {
                // Redis cache - clear specific keys
                $cacheKeys = Cache::getRedis()->keys('laravel_cache:api_cache:*salesmen*');
                
                if (!empty($cacheKeys)) {
                    foreach ($cacheKeys as $key) {
                        Cache::forget(str_replace('laravel_cache:', '', $key));
                    }
                    
                    Log::info('Cache invalidated', [
                        'type' => 'salesmen',
                        'keys_cleared' => count($cacheKeys),
                        'store' => 'redis'
                    ]);
                }
            } else {
                // Non-Redis cache (array, file, etc.) - clear common patterns
                $commonKeys = [
                    'api_cache:salesmen:index',
                    'api_cache:salesmen:list', 
                    'api_cache:salesmen:paginated',
                    'api_cache:codelists:all'
                ];
                
                foreach ($commonKeys as $key) {
                    Cache::forget($key);
                }
                
                Log::info('Cache invalidated', [
                    'type' => 'salesmen',
                    'keys_cleared' => count($commonKeys),
                    'store' => get_class($cacheStore)
                ]);
            }
        } catch (\Exception $e) {
            Log::warning('Cache invalidation failed', [
                'error' => $e->getMessage(),
                'type' => 'salesmen'
            ]);
        }
    }

    /**
     * Invalidate specific salesman cache by ID.
     */
    public function invalidateSalesmanCache(string $salesmanId): void
    {
        try {
            $patterns = [
                "api_cache:*salesmen/{$salesmanId}*",
                "api_cache:*salesmen*",
            ];

            foreach ($patterns as $pattern) {
                $cacheKeys = Cache::getRedis()->keys("laravel_cache:{$pattern}");
                
                foreach ($cacheKeys as $key) {
                    Cache::forget(str_replace('laravel_cache:', '', $key));
                }
            }
            
            Log::info('Salesman cache invalidated', ['salesman_id' => $salesmanId]);
        } catch (\Exception $e) {
            Log::warning('Salesman cache invalidation failed', [
                'error' => $e->getMessage(),
                'salesman_id' => $salesmanId
            ]);
        }
    }

    /**
     * Clear all API cache.
     */
    public function clearAllApiCache(): void
    {
        try {
            $cacheKeys = Cache::getRedis()->keys('laravel_cache:api_cache:*');
            
            foreach ($cacheKeys as $key) {
                Cache::forget(str_replace('laravel_cache:', '', $key));
            }
            
            Log::info('All API cache cleared', ['keys_cleared' => count($cacheKeys)]);
        } catch (\Exception $e) {
            Log::warning('API cache clear failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Get cache statistics.
     */
    public function getCacheStats(): array
    {
        try {
            $redis = Cache::getRedis();
            $apiCacheKeys = $redis->keys('laravel_cache:api_cache:*');
            
            $stats = [
                'total_api_cache_keys' => count($apiCacheKeys),
                'memory_usage' => $redis->info('memory')['used_memory_human'] ?? 'unknown',
                'cache_hits' => $redis->info('stats')['keyspace_hits'] ?? 0,
                'cache_misses' => $redis->info('stats')['keyspace_misses'] ?? 0,
            ];
            
            if ($stats['cache_hits'] + $stats['cache_misses'] > 0) {
                $stats['hit_ratio'] = round(
                    ($stats['cache_hits'] / ($stats['cache_hits'] + $stats['cache_misses'])) * 100,
                    2
                ) . '%';
            }
            
            return $stats;
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
}