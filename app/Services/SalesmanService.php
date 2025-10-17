<?php

namespace App\Services;

use App\Models\Salesman;
use App\Http\Requests\StoreSalesmanRequest;
use App\Http\Requests\UpdateSalesmanRequest;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service layer for Salesman business logic.
 * Separates business logic from controller concerns.
 */
class SalesmanService
{
    public function __construct(
        private CacheInvalidationService $cacheService
    ) {}

    /**
     * Get paginated salesmen with optional sorting and filtering
     * @param array<string, mixed> $filters
     * @return LengthAwarePaginator<int, Salesman>
     */
    public function getPaginatedSalesmen(array $filters = []): LengthAwarePaginator
    {
        $query = Salesman::query();
        
        // Extract parameters
        $sort = is_string($filters['sort'] ?? null) ? $filters['sort'] : null;
        $perPage = is_numeric($filters['per_page'] ?? 10) ? (int) ($filters['per_page'] ?? 10) : 10;
        
        // Apply sorting if provided
        if ($sort) {
            $direction = 'asc';
            if (str_starts_with($sort, '-')) {
                $direction = 'desc';
                $sort = substr($sort, 1);
            }
            
            $allowedSortFields = ['created_at', 'updated_at', 'first_name', 'last_name', 'email', 'prosight_id'];
            if (in_array($sort, $allowedSortFields, true)) {
                $query->orderBy($sort, $direction);
            }
        }
        
        return $query->paginate(min($perPage, 100));
    }
    
    /**
     * Create new salesman with proper validation and logging.
     */
    public function createSalesman(StoreSalesmanRequest $request): Salesman
    {
        return DB::transaction(function () use ($request) {
            $salesman = Salesman::create($request->validatedForDatabase());
            
            Log::channel('salesmen')->info('Salesman created', [
                'id' => $salesman->id,
                'prosight_id' => $salesman->prosight_id,
                'email' => $salesman->email,
            ]);
            
            // Invalidate related cache
            $this->cacheService->invalidateSalesmenCache();
            
            return $salesman;
        });
    }
    
    /**
     * Update existing salesman with audit logging.
     */
    public function updateSalesman(Salesman $salesman, UpdateSalesmanRequest $request): Salesman
    {
        return DB::transaction(function () use ($salesman, $request): Salesman {
            $originalData = $salesman->only(['first_name', 'last_name', 'prosight_id', 'email', 'phone']);
            $salesman->update($request->validatedForDatabase());
            $updatedSalesman = $salesman->fresh();
            
            if ($updatedSalesman) {
                $newData = $updatedSalesman->only(['first_name', 'last_name', 'prosight_id', 'email', 'phone']);
                
                Log::channel('salesmen')->info('Salesman updated', [
                    'id' => $salesman->id,
                    'original' => $originalData,
                    'updated' => $newData,
                ]);
                
                // Invalidate specific salesman cache
                $this->cacheService->invalidateSalesmanCache($salesman->id);
            }
            
            return $updatedSalesman ?? $salesman;
        });
    }
    
    /**
     * Delete salesman with proper cleanup and logging.
     */
    public function deleteSalesman(Salesman $salesman): bool
    {
        return DB::transaction(function () use ($salesman): bool {
            $salesmanData = [
                'id' => $salesman->id,
                'prosight_id' => $salesman->prosight_id,
                'email' => $salesman->email,
            ];
            
            $deleted = $salesman->delete();
            
            if ($deleted) {
                Log::channel('salesmen')->info('Salesman deleted', $salesmanData);
                
                // Invalidate specific salesman cache
                $this->cacheService->invalidateSalesmanCache($salesman->id);
            }
            
            return (bool) $deleted;
        });
    }
}