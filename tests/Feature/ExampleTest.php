<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * Test API welcome page returns proper JSON structure
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'version',
                'documentation',
                'endpoints',
                'deployment',
                'built_for'
            ])
            ->assertJson([
                'message' => 'Welcome to PROSIGHT Salesmen API',
                'version' => 'v1.0.0'
            ]);
    }
}
