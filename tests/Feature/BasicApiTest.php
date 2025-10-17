<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BasicApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_endpoint_works(): void
    {
        $response = $this->getJson('/api/health');
        
        $response->assertStatus(200);
    }

    public function test_codelists_endpoint_works(): void
    {
        $response = $this->getJson('/api/codelists');
        
        $response->assertStatus(200);
    }

    public function test_salesmen_index_endpoint_works(): void
    {
        $response = $this->getJson('/api/salesmen');
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'links',
                'meta'
            ]);
    }
}