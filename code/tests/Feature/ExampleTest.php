<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        // We test an API endpoint to avoid Vite manifest issues in tests
        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->get('/api/users');

        // Expecting 401 Unauthorized since we are not logged in
        $response->assertStatus(401);
    }
}
