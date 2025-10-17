<?php

namespace App\Http\Controllers;

use App\Services\CacheInvalidationService;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

#[OA\PathItem(
    path: "/api/v1/cache",
    summary: "Cache management endpoints",
    description: "Endpoints for managing API response cache"
)]
#[OA\Tag(
    name: "Cache Management",
    description: "Cache management and statistics endpoints"
)]
class CacheController extends Controller
{
    public function __construct(
        private CacheInvalidationService $cacheService
    ) {}

    /**
     * Get cache statistics.
     */
    #[OA\Get(
        path: "/api/cache/stats",
        operationId: "getCacheStats",
        summary: "Get cache statistics",
        description: "Retrieve cache performance metrics",
        tags: ["Cache Management"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Cache statistics retrieved successfully",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(property: "total_api_cache_keys", type: "integer", example: 42),
                        new OA\Property(property: "memory_usage", type: "string", example: "2.1M"),
                        new OA\Property(property: "cache_hits", type: "integer", example: 1250),
                        new OA\Property(property: "cache_misses", type: "integer", example: 150),
                        new OA\Property(property: "hit_ratio", type: "string", example: "89.3%")
                    ]
                )
            )
        ]
    )]
    public function stats(): JsonResponse
    {
        $stats = $this->cacheService->getCacheStats();
        
        return response()->json([
            'cache_stats' => $stats,
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Clear all API cache.
     */
    #[OA\Delete(
        path: "/api/cache/clear",
        operationId: "clearCache",
        summary: "Clear all API cache",
        description: "Remove all cached API responses",
        tags: ["Cache Management"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Cache cleared successfully",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Cache cleared successfully"),
                        new OA\Property(property: "timestamp", type: "string", format: "date-time")
                    ]
                )
            )
        ]
    )]
    public function clear(): JsonResponse
    {
        $this->cacheService->clearAllApiCache();
        
        return response()->json([
            'message' => 'Cache cleared successfully',
            'timestamp' => now()->toISOString(),
        ]);
    }
}