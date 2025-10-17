# Architecture Documentation

## 🐳 Docker Architecture

### Container Structure

The application uses Docker Compose for orchestration with two main services:

```yaml
services:
  app:                    # Laravel API Application
    build: .              # PHP 8.2-Apache container
    ports: 8000:80        # HTTP access
    depends_on:           # Waits for database
      postgres:
        condition: service_healthy
    
  postgres:               # PostgreSQL Database  
    image: postgres:16    # Latest stable PostgreSQL
    ports: 5432:5432      # Database access
    healthcheck:          # Service health monitoring
      test: ["CMD-SHELL", "pg_isready..."]
```

### Dockerfile Layers

**Multi-stage build optimized for production:**

1. **Base Layer**: PHP 8.2-Apache with PostgreSQL extensions
2. **Dependencies**: System packages and Composer installation  
3. **Application**: Laravel codebase and PHP dependencies
4. **Permissions**: Proper file ownership and storage permissions
5. **Startup**: Custom entrypoint script for initialization

### Startup Automation

The `docker-entrypoint.sh` script handles:
- Environment configuration (`.env.docker` → `.env`)
- Application key generation (`php artisan key:generate`)
- Database readiness waiting (`pg_isready`)
- Migrations (`php artisan migrate --force`)
- Cache optimization (`config:cache`, `route:cache`, `view:cache`)
- Permission setup for `storage/` and `bootstrap/cache/`

---

## Service Layer Pattern

This application implements a professional service layer architecture that separates business logic from HTTP concerns.

### SalesmanService

The `SalesmanService` class encapsulates all business logic related to salesman operations:

```php
<?php

namespace App\Services;

use App\Http\Requests\CreateSalesmanRequest;
use App\Http\Requests\UpdateSalesmanRequest;
use App\Models\Salesman;
use Illuminate\Database\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Pagination\LengthAwarePaginator;

class SalesmanService
{
    /**
     * Get paginated salesmen with optional sorting and filtering
     */
    public function getPaginatedSalesmen(array $filters = []): LengthAwarePaginator;

    /**
     * Create new salesman with proper validation and logging
     */
    public function createSalesman(CreateSalesmanRequest $request): Salesman;

    /**
     * Update existing salesman with audit logging
     */
    public function updateSalesman(Salesman $salesman, UpdateSalesmanRequest $request): Salesman;

    /**
     * Delete salesman with proper cleanup and logging
     */
    public function deleteSalesman(Salesman $salesman): bool;
}
```

### Key Benefits

1. **Separation of Concerns**: Business logic is separate from HTTP layer
2. **Testability**: Services can be unit tested independently
3. **Reusability**: Business logic can be reused across different controllers
4. **Transaction Safety**: All operations are wrapped in database transactions
5. **Audit Logging**: Comprehensive logging of all operations

### Controller Integration

Controllers are kept thin and delegate to services:

```php
class SalesmanController extends Controller
{
    public function __construct(
        private SalesmanService $salesmanService
    ) {}

    public function store(CreateSalesmanRequest $request): JsonResponse
    {
        try {
            $salesman = $this->salesmanService->createSalesman($request);
            return response()->json(new SalesmanResource($salesman), 201);
        } catch (\Exception $e) {
            return response()->json(
                ErrorResource::badRequest('Creation failed', $e->getMessage()),
                400
            );
        }
    }
}
```

## Logging Architecture

### Dedicated Channels

The application uses dedicated logging channels for different concerns:

-   `salesmen.log` - All salesman-related operations
-   `api.log` - General API access and errors
-   `laravel.log` - Framework and application logs

### Configuration

```php
// config/logging.php
'channels' => [
    'salesmen' => [
        'driver' => 'single',
        'path' => storage_path('logs/salesmen.log'),
        'level' => 'debug',
        'replace_placeholders' => true,
    ],
    'api' => [
        'driver' => 'single',
        'path' => storage_path('logs/api.log'),
        'level' => 'debug',
        'replace_placeholders' => true,
    ],
]
```

### Usage Examples

```php
// Service layer logging
Log::channel('salesmen')->info('Salesman created', [
    'id' => $salesman->id,
    'prosight_id' => $salesman->prosight_id,
    'email' => $salesman->email,
]);

// API access logging
Log::channel('api')->info('API request', [
    'method' => $request->method(),
    'url' => $request->url(),
    'ip' => $request->ip(),
]);
```

## Security Architecture

### Middleware Stack

1. **SecurityHeaders** - Adds security headers
2. **ApiLogging** - Logs API access
3. **RateLimiting** - Protects against abuse
4. **CORS** - Handles cross-origin requests

### Security Headers

```php
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        return $response->withHeaders([
            'X-Frame-Options' => 'DENY',
            'X-Content-Type-Options' => 'nosniff',
            'X-XSS-Protection' => '1; mode=block',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
        ]);
    }
}
```

### Rate Limiting

```php
// routes/api.php
Route::middleware(['throttle:api'])->group(function () {
    Route::apiResource('salesmen', SalesmanController::class);
});

// config/cache.php - 60 requests per minute per IP
RateLimiter::for('api', function (Request $request) {
    return Limit::perMinute(60)->by($request->ip());
});
```

## Database Architecture

### Performance Optimizations

```sql
-- Migration: 2025_10_13_140756_add_performance_indexes_to_salesmen_table.php
CREATE INDEX salesmen_prosight_id_index ON salesmen (prosight_id);
CREATE INDEX salesmen_email_index ON salesmen (email);
CREATE INDEX salesmen_created_at_index ON salesmen (created_at);
CREATE INDEX salesmen_updated_at_index ON salesmen (updated_at);
```

### Transaction Wrapping

All service operations are wrapped in database transactions for data integrity:

```php
public function createSalesman(CreateSalesmanRequest $request): Salesman
{
    return DB::transaction(function () use ($request): Salesman {
        $salesman = Salesman::create($request->validatedForDatabase());

        Log::channel('salesmen')->info('Salesman created', [
            'id' => $salesman->id,
            'prosight_id' => $salesman->prosight_id,
            'email' => $salesman->email,
        ]);

        return $salesman;
    });
}
```

## Health Check System

### Basic Health Check

```php
// HealthController::basic()
{
    "status": "healthy",
    "timestamp": "2025-10-17T12:30:45Z"
}
```

### Detailed Health Check

```php
// HealthController::detailed()
{
    "status": "healthy",
    "database": "connected",
    "cache": "working",
    "storage": "writable",
    "version": "1.0.0",
    "timestamp": "2025-10-17T12:30:45Z"
}
```

## API Versioning

The application supports API versioning for future compatibility:

```php
// routes/api.php
Route::prefix('v1')->group(function () {
    Route::apiResource('salesmen', SalesmanController::class);
    Route::get('codelists', [CodelistController::class, 'index']);
});

// Legacy support (no prefix)
Route::apiResource('salesmen', SalesmanController::class);
```

This architecture ensures the application is production-ready with enterprise-grade patterns and practices.
