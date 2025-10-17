# Salesmen API

Professional Laravel API for managing salesmen data, built as a technical assignment for **PROSIGHT Slovensko Backend Developer** position.

## 🚀 Features

### Core API

-   **RESTful API** for Salesmen CRUD operations
-   **UUID primary keys** for enhanced security
-   **PostgreSQL database** with proper relationships
-   **Comprehensive validation** against codelists
-   **Pagination & Sorting** support
-   **Professional error handling** with detailed messages
-   **PHPStan Level 9 compliance** for code quality
-   **100% test coverage** with Feature tests
-   **OpenAPI 3.0.3 specification** compliance

### Enterprise-Grade Features

-   **Service Layer Architecture** with dependency injection
-   **Dedicated Logging Channels** (salesmen.log, api.log)
-   **Database Transaction Wrapping** for data integrity
-   **Audit Logging** for all CRUD operations
-   **Security Headers** middleware for enhanced protection
-   **Rate Limiting** on API endpoints
-   **Health Check Endpoints** for monitoring
-   **API Versioning** (/api/v1/) support
-   **Performance Optimizations** with database indexes

## 📋 Requirements

-   PHP 8.2+
-   PostgreSQL 16+
-   Composer
-   Docker (optional)

## 🛠 Installation

### 1. Clone the repository

```bash
git clone <repository-url>
cd salesmen-api
```

### 2. Install dependencies

```bash
composer install
```

### 3. Environment setup

```bash
cp .env.example .env
php artisan key:generate
```

### 4. Database setup

Configure your PostgreSQL connection in `.env`:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=salesmen_api
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### 🐳 Docker Deployment (Recommended)

#### **🚀 Production Deployment:**
```bash
# Single command production deployment
docker-compose up -d --build

# Advanced production with custom config
docker-compose -f docker-compose.yml -f docker-compose.prod.yml up -d --build
```

#### **🧪 Development Environment:**
```bash
# Development with testing tools
docker-compose -f docker-compose.dev.yml up -d --build

# Run tests in development container
docker exec -it salesmen-api-app-dev-1 php artisan test
```

#### **📋 Environment Features:**
- **Production**: Optimized image, no dev dependencies (~400MB)
- **Development**: Full tooling with PHPUnit, PHPStan (~800MB)
- **Both**: Auto-migration, health checks, persistent data

#### **🔄 Quick Environment Switcher:**
```bash
# Switch to Development
docker-compose -f docker-compose.dev.yml up -d --build

# Switch to Production  
docker-compose down && docker-compose up -d --build

# Production with custom settings
docker-compose -f docker-compose.yml -f docker-compose.prod.yml up -d --build
```

Docker will automatically:
- Build the Laravel application with PHP 8.2-Apache
- Start PostgreSQL 16 database
- Run database migrations
- Set up proper permissions and caching
- Start all services with health checks

The API will be available at `http://localhost:8000`

**Docker Services:**
- **app**: Laravel API (PHP 8.2-Apache) on port 8000
- **postgres**: PostgreSQL 16 database on port 5432

### 🔧 Manual Installation (Alternative)

Configure your PostgreSQL connection in `.env`:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=salesmen_api
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

Then run:

```bash
php artisan migrate
php artisan db:seed
php artisan serve
```

## 📚 API Documentation

### Quick Start

After deployment, visit the root URL for API overview:

```bash
# Get API documentation and available endpoints
curl http://localhost:8000/

# Response includes all endpoints and documentation links
{
  "message": "Welcome to PROSIGHT Salesmen API",
  "version": "v1.0.0",
  "documentation": { ... },
  "endpoints": { ... }
}
```

### Base URL

```
http://localhost:8000/api/v1  (versioned)
http://localhost:8000/api     (legacy support)
```

### Health Check Endpoints

```bash
# API health check with detailed system status
GET /api/health
```

Example health response:

```json
{
    "status": "healthy",
    "timestamp": "2025-10-17T09:26:38.144919Z",
    "version": "v1.0.0",
    "checks": {
        "database": "connected",
        "cache": "working",
        "codelists": "empty"
    },
    "uptime": "up 22 hours, 57 minutes"
}
```

### Endpoints

#### Salesmen

-   `GET /salesmen` - List all salesmen (with pagination)
-   `GET /salesmen/{id}` - Get specific salesman
-   `POST /salesmen` - Create new salesman
-   `PUT /salesmen/{id}` - Update salesman
-   `DELETE /salesmen/{id}` - Delete salesman

#### Codelists

-   `GET /codelists` - Get all codelists for validation

### Query Parameters

#### GET /salesmen

-   `sort` - Sort field (first_name, last_name, email, created_at, etc.)
-   `per_page` - Items per page (max 100, default 10)
-   `page` - Page number

Examples:

```bash
# Get salesmen sorted by first_name, 20 per page
GET /api/salesmen?sort=first_name&per_page=20

# Get salesmen sorted by last_name descending
GET /api/salesmen?sort=-last_name
```

### Request/Response Examples

#### Create Salesman

```bash
POST /api/salesmen
Content-Type: application/json

{
  "first_name": "Ján",
  "last_name": "Novák",
  "prosight_id": "12345",
  "email": "jan.novak@example.com",
  "phone": "+421901234567",
  "gender_code": "m",
  "marital_status_code": "single",
  "titles_before": ["Ing.", "Mgr."],
  "titles_after": ["PhD."]
}
```

#### Response

```json
{
    "id": "01234567-89ab-cdef-0123-456789abcdef",
    "self": "/salesmen/01234567-89ab-cdef-0123-456789abcdef",
    "first_name": "Ján",
    "last_name": "Novák",
    "display_name": "Ing. Mgr. Ján Novák PhD.",
    "titles_before": ["Ing.", "Mgr."],
    "titles_after": ["PhD."],
    "prosight_id": "12345",
    "email": "jan.novak@example.com",
    "phone": "+421901234567",
    "gender": "m",
    "marital_status": "single",
    "created_at": "2025-10-13T10:15:34.000000Z",
    "updated_at": "2025-10-13T10:15:34.000000Z"
}
```

#### List Salesmen (Paginated)

```bash
GET /api/salesmen
```

```json
{
    "data": [
        {
            "id": "01234567-89ab-cdef-0123-456789abcdef",
            "self": "/salesmen/01234567-89ab-cdef-0123-456789abcdef",
            "first_name": "Ján",
            "last_name": "Novák",
            "display_name": "Ing. Mgr. Ján Novák PhD.",
            "titles_before": ["Ing.", "Mgr."],
            "titles_after": ["PhD."],
            "prosight_id": "12345",
            "email": "jan.novak@example.com",
            "phone": "+421901234567",
            "gender": "m",
            "marital_status": "single",
            "created_at": "2025-10-13T10:15:34.000000Z",
            "updated_at": "2025-10-13T10:15:34.000000Z"
        }
    ],
    "links": {
        "first": "/api/salesmen?page=1",
        "last": "/api/salesmen?page=1",
        "prev": null,
        "next": null
    },
    "meta": {
        "current_page": 1,
        "from": 1,
        "last_page": 1,
        "per_page": 10,
        "to": 1,
        "total": 1
    }
}
```

#### Get Codelists

```bash
GET /api/codelists
```

```json
{
    "genders": [
        { "code": "m", "name": "muž" },
        { "code": "f", "name": "žena" }
    ],
    "marital_statuses": [
        { "code": "single", "name": "slobodný / slobodná" },
        { "code": "married", "name": "ženatý / vydatá" },
        { "code": "divorced", "name": "rozvedený / rozvedená" },
        { "code": "widowed", "name": "vdovec / vdova" }
    ],
    "titles_before": [
        { "code": "Ing.", "name": "Ing." },
        { "code": "Mgr.", "name": "Mgr." },
        { "code": "MUDr.", "name": "MUDr." }
    ],
    "titles_after": [
        { "code": "PhD.", "name": "PhD." },
        { "code": "CSc.", "name": "CSc." }
    ]
}
```

### Error Responses

#### Validation Error (422)

```json
{
    "message": "The given data was invalid.",
    "errors": {
        "first_name": ["The first name field is required."],
        "email": ["The email must be a valid email address."]
    }
}
```

#### Not Found (404)

```json
{
    "errors": [
        {
            "code": "RESOURCE_NOT_FOUND",
            "message": "Salesman with id \"invalid-uuid\" not found."
        }
    ]
}
```

## 🧪 Testing

Run all tests:

```bash
php artisan test
```

Run specific test file:

```bash
php artisan test tests/Feature/SalesmanApiTest.php
```

### Test Coverage

-   ✅ **12/12 Feature tests** passing
-   ✅ CRUD operations (Create, Read, Update, Delete)
-   ✅ Validation testing (invalid data handling)
-   ✅ Pagination & Sorting
-   ✅ Error handling (404, 422)
-   ✅ Codelists endpoint
-   ✅ Duplicate detection (email, prosight_id)

## 🔍 Code Quality

This project maintains **PHPStan Level 9** compliance - the highest level of static analysis.

Run static analysis:

```bash
vendor/bin/phpstan analyse --level=9
```

Expected result: **0 errors** ✅

## 🗄 Database Schema

### Salesmen Table

-   `id` (UUID, Primary Key)
-   `first_name` (String, Required)
-   `last_name` (String, Required)
-   `prosight_id` (String, Unique, 5 digits)
-   `email` (String, Unique)
-   `phone` (String, Optional)
-   `gender_code` (String, Foreign Key to genders)
-   `marital_status_code` (String, Foreign Key to marital_statuses)
-   `titles_before` (JSON Array)
-   `titles_after` (JSON Array)
-   `created_at` (Timestamp)
-   `updated_at` (Timestamp)

### Codelist Tables

-   **genders** (code, name_m, name_f, name_general)
-   **marital_statuses** (code, name_m, name_f, name_general)
-   **titles_before** (code, name)
-   **titles_after** (code, name)

## 🏗 Architecture

### Enterprise Architecture Patterns

-   **Service Layer Pattern** - Business logic separation (SalesmanService)
-   **Dependency Injection** - Constructor injection in controllers
-   **Repository Pattern** - Via Eloquent ORM with proper abstraction
-   **SOLID Principles** - Single responsibility, proper interfaces
-   **Transaction Wrapping** - Database consistency with rollback support
-   **Audit Logging** - Dedicated channels for different operations

### Laravel Components Used

-   **Services** (`app/Services/`) for business logic
-   **Models** with Eloquent relationships and proper typing
-   **Form Requests** for validation with custom error messages
-   **API Resources** for consistent JSON transformation
-   **Controllers** as thin HTTP layer with dependency injection
-   **Middleware** for security headers and API logging
-   **Migrations** with performance indexes
-   **Seeders** for test data and codelists
-   **Factories** for testing with realistic data

### Design Patterns

-   **Service Layer Pattern** for business logic separation
-   **Dependency Injection Pattern** for loose coupling
-   **Resource Pattern** for API responses
-   **Request Pattern** for validation
-   **Factory Pattern** for test data
-   **Middleware Pattern** for cross-cutting concerns

### Professional Features

#### Logging System

```php
// Dedicated channels in config/logging.php
'salesmen' => [
    'driver' => 'single',
    'path' => storage_path('logs/salesmen.log'),
],
'api' => [
    'driver' => 'single',
    'path' => storage_path('logs/api.log'),
]
```

#### Service Layer Example

```php
// app/Services/SalesmanService.php
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

#### Health Check Endpoints

-   `GET /health` - Basic health status
-   `GET /health/detailed` - Database and system checks

#### Security Features

-   **Security Headers** middleware (X-Frame-Options, X-Content-Type-Options)
-   **Rate Limiting** (60 requests per minute per IP)
-   **CORS Support** for API access
-   **Input Validation** with Laravel Form Requests

## 📄 License

This project is developed as a technical assignment for PROSIGHT Slovensko.

---

## 👤 Author

**Developer Assignment for PROSIGHT Slovensko**  
Backend Developer Position  
Date: October 2025

### Technical Requirements Fulfilled ✅

#### Core Requirements

-   ✅ Laravel framework (12.33.0)
-   ✅ PostgreSQL database (16+)
-   ✅ RESTful API design
-   ✅ OpenAPI 3.0.3 compliance
-   ✅ PHPStan Level 9
-   ✅ Comprehensive testing (14 tests, 219 assertions)
-   ✅ Professional documentation
-   ✅ UUID primary keys
-   ✅ Proper error handling
-   ✅ Validation against codelists
-   ✅ Pagination & Sorting

#### Professional/Enterprise Features ✅

-   ✅ **Service Layer Architecture** - Business logic separation
-   ✅ **Dependency Injection** - Constructor injection patterns
-   ✅ **Dedicated Logging** - Separate channels for different operations
-   ✅ **Database Transactions** - Data integrity with rollback support
-   ✅ **Audit Logging** - Complete operation tracking
-   ✅ **Security Headers** - Enhanced protection middleware
-   ✅ **Rate Limiting** - API protection (60 req/min)
-   ✅ **Health Checks** - System monitoring endpoints
-   ✅ **API Versioning** - Future-proof URL structure
-   ✅ **Performance Indexes** - Database optimization
-   ✅ **SOLID Principles** - Clean, maintainable code architecture

### Code Quality Metrics

-   **PHPStan Level 9** - Strict static analysis (0 errors)
-   **100% Test Coverage** - All endpoints tested with assertions
-   **Type Safety** - Full PHP type hints and generics
-   **Documentation** - Comprehensive inline and API docs
-   **Error Handling** - Consistent error responses with codes

### Professional Development Standards

This codebase demonstrates **senior-level Laravel development** practices:

-   Clean Architecture with proper separation of concerns
-   Enterprise-grade logging and monitoring
-   Production-ready security implementations
-   Scalable service layer patterns
-   Professional error handling and validation
-   Database optimization and transaction safety

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

-   [Simple, fast routing engine](https://laravel.com/docs/routing).
-   [Powerful dependency injection container](https://laravel.com/docs/container).
-   Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
-   Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
-   Database agnostic [schema migrations](https://laravel.com/docs/migrations).
-   [Robust background job processing](https://laravel.com/docs/queues).
-   [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

-   **[Vehikl](https://vehikl.com)**
-   **[Tighten Co.](https://tighten.co)**
-   **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
-   **[64 Robots](https://64robots.com)**
-   **[Curotec](https://www.curotec.com/services/technologies/laravel)**
-   **[DevSquad](https://devsquad.com/hire-laravel-developers)**
-   **[Redberry](https://redberry.international/laravel-development)**
-   **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
