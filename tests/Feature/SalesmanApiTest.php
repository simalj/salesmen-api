<?php

namespace Tests\Feature;

use App\Models\Gender;
use App\Models\MaritalStatus;
use App\Models\Salesman;
use App\Models\TitleAfter;
use App\Models\TitleBefore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class SalesmanApiTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed codelists pre testy
        $this->seed([
            \Database\Seeders\GenderSeeder::class,
            \Database\Seeders\MaritalStatusSeeder::class,
            \Database\Seeders\TitleBeforeSeeder::class,
            \Database\Seeders\TitleAfterSeeder::class,
        ]);
    }

    /** @test */
    public function test_can_get_all_salesmen()
    {
        // Arrange - vytvoríme testovacích salesmen
        Salesman::factory()->count(3)->create();

        // Act
        $response = $this->getJson('/api/salesmen');

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'self',
                        'first_name',
                        'last_name',
                        'display_name',
                        'titles_before',
                        'titles_after',
                        'prosight_id',
                        'email',
                        'phone',
                        'gender',
                        'marital_status',
                        'created_at',
                        'updated_at',
                    ]
                ],
                'links' => [
                    'first',
                    'last',
                    'prev',
                    'next'
                ],
                'meta' => [
                    'current_page',
                    'from',
                    'last_page',
                    'per_page',
                    'to',
                    'total'
                ]
            ]);
    }

    /** @test */
    public function test_can_get_single_salesman()
    {
        // Arrange
        $salesman = Salesman::factory()->create([
            'first_name' => 'Ján',
            'last_name' => 'Novák',
            'titles_before' => ['Ing.'],
            'titles_after' => ['PhD.']
        ]);

        // Act
        $response = $this->getJson("/api/salesmen/{$salesman->id}");

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'id' => $salesman->id,
                'first_name' => 'Ján',
                'last_name' => 'Novák',
                'display_name' => 'Ing. Ján Novák PhD.',
                'titles_before' => ['Ing.'],
                'titles_after' => ['PhD.'],
                'self' => "/salesmen/{$salesman->id}"
            ]);
    }

    /** @test */
    public function test_can_create_salesman()
    {
        // Arrange - valid data
        $salesmanData = [
            'first_name' => 'Peter',
            'last_name' => 'Svoboda',
            'prosight_id' => '12345',
            'email' => 'peter.svoboda@example.com',
            'phone' => '+421901234567',
            'gender_code' => 'm',
            'marital_status_code' => 'single',
            'titles_before' => ['Mgr.'],
            'titles_after' => ['PhD.']
        ];

        // Act
        $response = $this->postJson('/api/salesmen', $salesmanData);

        // Assert
        $response->assertStatus(201)
            ->assertJsonStructure([
                'id',
                'first_name',
                'last_name',
                'display_name',
                'prosight_id',
                'email',
                'phone',
                'gender',
                'marital_status',
                'titles_before',
                'titles_after',
                'created_at',
                'updated_at'
            ]);

        $this->assertDatabaseHas('salesmen', [
            'first_name' => 'Peter',
            'last_name' => 'Svoboda',
            'prosight_id' => '12345',
            'email' => 'peter.svoboda@example.com'
        ]);
    }

    /** @test */
    public function test_create_salesman_validation_fails_for_invalid_data()
    {
        // Arrange - nevalidné dáta
        $invalidData = [
            'first_name' => 'P', // príliš krátke
            'last_name' => '', // prázdne
            'prosight_id' => '123', // nesprávny formát
            'email' => 'invalid-email', // nevalidný email
            'gender_code' => 'invalid', // neexistujúci kód
            'marital_status_code' => 'invalid' // neexistujúci kód
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

    /** @test */
    public function test_can_update_salesman()
    {
        // Arrange
        $salesman = Salesman::factory()->create();
        $updateData = [
            'first_name' => 'Updated Name',
            'phone' => '+421987654321'
        ];

        // Act
        $response = $this->putJson("/api/salesmen/{$salesman->id}", $updateData);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'id' => $salesman->id,
                'first_name' => 'Updated Name',
                'phone' => '+421987654321'
            ]);

        $this->assertDatabaseHas('salesmen', [
            'id' => $salesman->id,
            'first_name' => 'Updated Name',
            'phone' => '+421987654321'
        ]);
    }

    /** @test */
    public function test_can_delete_salesman()
    {
        // Arrange
        $salesman = Salesman::factory()->create();

        // Act
        $response = $this->deleteJson("/api/salesmen/{$salesman->id}");

        // Assert
        $response->assertStatus(204);
        $this->assertDatabaseMissing('salesmen', ['id' => $salesman->id]);
    }

    /** @test */
    public function test_returns_404_for_non_existent_salesman()
    {
        // Act
        $response = $this->getJson('/api/salesmen/non-existent-uuid');

        // Assert
        $response->assertStatus(404)
            ->assertJsonStructure([
                'errors' => [
                    '*' => [
                        'code',
                        'message'
                    ]
                ]
            ]);
    }

    /** @test */
    public function test_can_sort_salesmen()
    {
        // Arrange
        Salesman::factory()->create(['first_name' => 'Anna']);
        Salesman::factory()->create(['first_name' => 'Boris']);
        Salesman::factory()->create(['first_name' => 'Cyril']);

        // Act - sort by first_name ascending
        $response = $this->getJson('/api/salesmen?sort=first_name');

        // Assert
        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertEquals('Anna', $data[0]['first_name']);
        $this->assertEquals('Boris', $data[1]['first_name']);
        $this->assertEquals('Cyril', $data[2]['first_name']);
    }

    /** @test */
    public function test_can_paginate_salesmen()
    {
        // Arrange
        Salesman::factory()->count(25)->create();

        // Act
        $response = $this->getJson('/api/salesmen?per_page=10');

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'meta' => [
                    'per_page' => 10,
                    'total' => 25,
                    'current_page' => 1,
                    'last_page' => 3
                ]
            ]);
    }

    /** @test */  
    public function test_can_get_codelists()
    {
        // Act
        $response = $this->getJson('/api/codelists');

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'genders' => [
                    '*' => ['code', 'name']
                ],
                'marital_statuses' => [
                    '*' => ['code', 'name']
                ],
                'titles_before' => [
                    '*' => ['code', 'name']
                ],
                'titles_after' => [
                    '*' => ['code', 'name']
                ]
            ]);

        // Verify some expected data
        $response->assertJsonFragment(['code' => 'm'])
            ->assertJsonFragment(['code' => 'f'])
            ->assertJsonFragment(['code' => 'single'])
            ->assertJsonFragment(['code' => 'married']);
    }

    /** @test */
    public function test_duplicate_prosight_id_returns_conflict()
    {
        // Arrange
        Salesman::factory()->create(['prosight_id' => '12345']);

        $duplicateData = [
            'first_name' => 'Duplicate',
            'last_name' => 'User',
            'prosight_id' => '12345', // duplicate
            'email' => 'duplicate@example.com',
            'gender_code' => 'm'
        ];

        // Act
        $response = $this->postJson('/api/salesmen', $duplicateData);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['prosight_id'])
            ->assertJson([
                'message' => 'Salesman with this Prosight ID already exists.',
                'errors' => [
                    'prosight_id' => [
                        'Salesman with this Prosight ID already exists.'
                    ]
                ]
            ]);
    }

    /** @test */
    public function test_duplicate_email_returns_conflict()
    {
        // Arrange
        Salesman::factory()->create(['email' => 'test@example.com']);

        $duplicateData = [
            'first_name' => 'Duplicate',
            'last_name' => 'User',
            'prosight_id' => '54321',
            'email' => 'test@example.com', // duplicate
            'gender_code' => 'm'
        ];

        // Act
        $response = $this->postJson('/api/salesmen', $duplicateData);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email'])
            ->assertJson([
                'message' => 'Salesman with this email already exists.',
                'errors' => [
                    'email' => [
                        'Salesman with this email already exists.'
                    ]
                ]
            ]);
    }

    /** @test */
    public function test_health_check_endpoint()
    {
        // Act
        $response = $this->getJson('/api/health');

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'timestamp',
                'version',
                'checks' => [
                    'database',
                    'cache',
                    'codelists'
                ],
                'uptime'
            ])
            ->assertJson([
                'status' => 'healthy',
                'version' => 'v1.0.0'
            ]);
    }

    /** @test */
    public function test_api_versioning_v1_endpoints()
    {
        // Arrange
        Salesman::factory()->create();

        // Act - Test v1 explicitly
        $response = $this->getJson('/api/v1/salesmen');

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure(['data', 'links', 'meta']);
    }
}
