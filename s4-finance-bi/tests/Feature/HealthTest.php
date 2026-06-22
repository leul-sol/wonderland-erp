<?php

namespace Tests\Feature;

use Tests\TestCase;

class HealthTest extends TestCase
{
    public function test_health_endpoint_returns_s4_payload(): void
    {
        $response = $this->getJson('/api/v1/health');

        $response->assertOk()
            ->assertJson([
                'status' => 'ok',
                'system' => 'S4',
            ])
            ->assertJsonStructure(['status', 'system', 'version']);
    }
}
