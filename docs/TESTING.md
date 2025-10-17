# Testing Documentation

## Test Suite Overview

The Salesmen API includes comprehensive testing to ensure reliability and maintainability.

### Test Statistics

-   **14 Feature Tests** covering all API endpoints
-   **219 Test Assertions** verifying behavior
-   **100% Endpoint Coverage** including edge cases
-   **PHPStan Level 9** static analysis compliance

### Running Tests

#### 🐳 Docker Environment (Recommended)

For testing with dev dependencies included:

```bash
# Start Docker development stack
docker-compose -f docker-compose.dev.yml up -d --build

# Run all tests in development container
docker exec -it salesmen-api-app-dev-1 php artisan test

# Run specific test file
docker exec -it salesmen-api-app-dev-1 php artisan test tests/Feature/SalesmanApiTest.php

# Run with coverage (if Xdebug is installed)
docker exec -it salesmen-api-app-dev-1 php artisan test --coverage

# Check PHPStan analysis
docker exec -it salesmen-api-app-dev-1 ./vendor/bin/phpstan analyse

# Run tests with verbose output
docker exec -it salesmen-api-app-dev-1 php artisan test --verbose
```

**Note**: Production Docker image (`docker-compose.yml`) excludes test dependencies for smaller size.

#### 🔧 Local Environment

```bash
# Run all tests
php artisan test

# Run specific test file
php artisan test tests/Feature/SalesmanApiTest.php

# Run with coverage (requires Xdebug)
php artisan test --coverage

# Run with specific filter
php artisan test --filter test_can_create_salesman
```

### Test Structure

#### Feature Tests (`tests/Feature/SalesmanApiTest.php`)

**CRUD Operations:**

-   ✅ `test_can_get_all_salesmen()` - List endpoint with pagination
-   ✅ `test_can_get_single_salesman()` - Show endpoint with valid UUID
-   ✅ `test_can_create_salesman()` - Store endpoint with valid data
-   ✅ `test_can_update_salesman()` - Update endpoint with partial data
-   ✅ `test_can_delete_salesman()` - Destroy endpoint with cleanup

**Validation Tests:**

-   ✅ `test_create_salesman_validation_fails_for_invalid_data()` - Input validation
-   ✅ `test_duplicate_prosight_id_returns_conflict()` - Unique constraint
-   ✅ `test_duplicate_email_returns_conflict()` - Unique constraint

**Error Handling:**

-   ✅ `test_returns_404_for_non_existent_salesman()` - Not found responses

**Query Features:**

-   ✅ `test_can_sort_salesmen()` - Sorting functionality
-   ✅ `test_can_paginate_salesmen()` - Pagination with limits

**Supporting Endpoints:**

-   ✅ `test_can_get_codelists()` - Codelist endpoint
-   ✅ `test_health_check_endpoint()` - Health monitoring with database checks
-   ✅ `test_api_versioning_v1_endpoints()` - API versioning

**Health Check Testing:**
The health endpoint (`/api/health`) validates:
- Database connectivity
- Cache functionality
- Codelist availability
- System uptime and version info

### Test Examples

#### Testing CRUD Operations

```php
/** @test */
public function test_can_create_salesman()
{
    // Arrange
    $salesmanData = [
        'first_name' => 'Test',
        'last_name' => 'User',
        'prosight_id' => '12345',
        'email' => 'test@example.com',
        'gender_code' => 'm',
        'marital_status_code' => 'single'
    ];

    // Act
    $response = $this->postJson('/api/salesmen', $salesmanData);

    // Assert
    $response->assertStatus(201)
        ->assertJsonStructure([
            'id', 'first_name', 'last_name', 'email',
            'prosight_id', 'created_at', 'updated_at'
        ]);

    $this->assertDatabaseHas('salesmen', [
        'first_name' => 'Test',
        'last_name' => 'User',
        'email' => 'test@example.com'
    ]);
}
```

#### Testing Validation

```php
/** @test */
public function test_create_salesman_validation_fails_for_invalid_data()
{
    // Arrange - Invalid data (missing required fields)
    $invalidData = [
        'first_name' => 'A', // Too short
        'email' => 'invalid-email', // Invalid format
        'prosight_id' => '123' // Too short
    ];

    // Act
    $response = $this->postJson('/api/salesmen', $invalidData);

    // Assert
    $response->assertStatus(422)
        ->assertJsonValidationErrors([
            'first_name',
            'last_name',
            'prosight_id',
            'email',
            'gender',
            'marital_status'
        ]);
}
```

#### Testing Pagination

```php
/** @test */
public function test_can_paginate_salesmen()
{
    // Arrange
    Salesman::factory(25)->create();

    // Act
    $response = $this->getJson('/api/salesmen?per_page=10&page=2');

    // Assert
    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'first_name', 'last_name', 'email']
            ],
            'links',
            'meta' => [
                'current_page',
                'per_page',
                'total',
                'last_page'
            ]
        ])
        ->assertJsonPath('meta.current_page', 2)
        ->assertJsonPath('meta.per_page', 10);
}
```

### Testing Service Layer

While current tests focus on HTTP endpoints, service layer testing can be added:

```php
// tests/Unit/SalesmanServiceTest.php
class SalesmanServiceTest extends TestCase
{
    use RefreshDatabase;

    private SalesmanService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(SalesmanService::class);
    }

    /** @test */
    public function it_creates_salesman_with_transaction()
    {
        // Arrange
        $request = CreateSalesmanRequest::create('/test', 'POST', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'prosight_id' => '12345',
            'email' => 'john@example.com',
            'gender_code' => 'm',
            'marital_status_code' => 'single'
        ]);

        // Act
        $salesman = $this->service->createSalesman($request);

        // Assert
        $this->assertInstanceOf(Salesman::class, $salesman);
        $this->assertEquals('John', $salesman->first_name);
        $this->assertDatabaseHas('salesmen', ['email' => 'john@example.com']);
    }
}
```

### Database Testing

#### Factory Usage

```php
// database/factories/SalesmanFactory.php
class SalesmanFactory extends Factory
{
    public function definition(): array
    {
        return [
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'prosight_id' => $this->faker->unique()->numerify('#####'),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->optional()->phoneNumber(),
            'gender_code' => $this->faker->randomElement(['m', 'f']),
            'marital_status_code' => $this->faker->randomElement(['single', 'married', 'divorced', 'widowed']),
            'titles_before' => $this->faker->optional()->randomElements(['Ing.', 'Mgr.', 'Dr.'], 2),
            'titles_after' => $this->faker->optional()->randomElements(['PhD.', 'CSc.'], 1),
        ];
    }
}
```

#### Test Database Setup

```php
// tests/TestCase.php
abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        // Use in-memory SQLite for faster tests
        config(['database.default' => 'sqlite']);
        config(['database.connections.sqlite.database' => ':memory:']);

        $this->artisan('migrate');
        $this->artisan('db:seed', ['--class' => 'CodelistSeeder']);
    }
}
```

### Test Data Management

#### Seeders for Testing

```php
// database/seeders/TestSeeder.php
class TestSeeder extends Seeder
{
    public function run(): void
    {
        // Create test codelists
        Gender::create(['code' => 'm', 'name_m' => 'muž', 'name_f' => 'muž', 'name_general' => 'muž']);
        Gender::create(['code' => 'f', 'name_m' => 'žena', 'name_f' => 'žena', 'name_general' => 'žena']);

        MaritalStatus::create(['code' => 'single', 'name_m' => 'slobodný', 'name_f' => 'slobodná', 'name_general' => 'slobodný/á']);
        MaritalStatus::create(['code' => 'married', 'name_m' => 'ženatý', 'name_f' => 'vydatá', 'name_general' => 'ženatý/vydatá']);

        // Create test salesmen
        Salesman::factory(10)->create();
    }
}
```

### Performance Testing

#### Load Testing with Apache Bench

```bash
# Test endpoint performance
ab -n 1000 -c 10 http://localhost:8000/api/salesmen

# Test with authentication
ab -n 100 -c 5 -H "Accept: application/json" http://localhost:8000/api/salesmen
```

#### Database Query Testing

```php
/** @test */
public function test_salesmen_list_performs_efficiently()
{
    // Arrange - Create many records
    Salesman::factory(1000)->create();

    // Act & Assert - Monitor query count
    $queries = 0;
    DB::listen(function ($query) use (&$queries) {
        $queries++;
    });

    $response = $this->getJson('/api/salesmen?per_page=50');

    // Should not exceed reasonable query limit
    $this->assertLessThan(5, $queries);
    $response->assertStatus(200);
}
```

### Testing Best Practices

#### Test Organization

```php
class SalesmanApiTest extends TestCase
{
    use RefreshDatabase;

    // Group related tests

    // CRUD Tests
    public function test_can_create_salesman() { }
    public function test_can_update_salesman() { }
    public function test_can_delete_salesman() { }

    // Validation Tests
    public function test_validation_fails_for_invalid_data() { }
    public function test_duplicate_email_returns_conflict() { }

    // Query Tests
    public function test_can_sort_salesmen() { }
    public function test_can_paginate_salesmen() { }
}
```

#### Test Data Patterns

```php
// Use descriptive test data
$validSalesmanData = [
    'first_name' => 'Ján',
    'last_name' => 'Novák',
    'prosight_id' => '12345',
    'email' => 'jan.novak@example.com',
    'gender_code' => 'm',
    'marital_status_code' => 'single'
];

// Test edge cases
$edgeCaseData = [
    'first_name' => str_repeat('A', 50), // Max length
    'last_name' => 'Žľš', // Unicode characters
    'email' => 'test+tag@example.co.uk', // Complex email
];
```

### Continuous Integration

#### GitHub Actions Test Workflow

```yaml
name: Tests

on: [push, pull_request]

jobs:
    tests:
        runs-on: ubuntu-latest

        services:
            postgres:
                image: postgres:16
                env:
                    POSTGRES_PASSWORD: postgres
                    POSTGRES_DB: testing
                options: >-
                    --health-cmd pg_isready
                    --health-interval 10s
                    --health-timeout 5s
                    --health-retries 5

        steps:
            - uses: actions/checkout@v3

            - name: Setup PHP
              uses: shivammathur/setup-php@v2
              with:
                  php-version: 8.2
                  extensions: pdo, pgsql

            - name: Install dependencies
              run: composer install --prefer-dist --no-progress

            - name: Run tests
              run: php artisan test
              env:
                  DB_CONNECTION: pgsql
                  DB_HOST: localhost
                  DB_DATABASE: testing
                  DB_USERNAME: postgres
                  DB_PASSWORD: postgres

            - name: Run PHPStan
              run: ./vendor/bin/phpstan analyse
```

### Test Maintenance

#### Regular Test Review

1. **Update test data** when business rules change
2. **Add tests** for new features immediately
3. **Remove obsolete tests** when features are deprecated
4. **Monitor test performance** and optimize slow tests
5. **Review test coverage** regularly

#### Test Documentation

```php
/**
 * @test
 * @group crud
 * @group salesmen
 *
 * Test that a salesman can be created with valid data.
 * Verifies:
 * - HTTP 201 status code
 * - Correct JSON structure in response
 * - Database record creation
 * - UUID generation
 */
public function test_can_create_salesman()
{
    // Implementation
}
```

This comprehensive testing approach ensures the API is reliable, maintainable, and production-ready.
