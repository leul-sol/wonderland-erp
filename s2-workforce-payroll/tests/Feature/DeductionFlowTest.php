<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MocksS2Auth;
use Tests\TestCase;

class DeductionFlowTest extends TestCase
{
    use MocksS2Auth;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.internal_key_current' => 'test-service-key']);

        $this->seed(DatabaseSeeder::class);
    }

    public function test_staff_meal_deduction_via_service_key_is_idempotent(): void
    {
        $employeeId = $this->postJson('/api/v1/employees', [
            'full_name' => 'Liya Assefa',
            'base_salary' => 9000,
        ], $this->authHeaders())->json('data.id');

        $headers = [
            'X-Service-Key' => 'test-service-key',
            'Idempotency-Key' => 'meal-deduction-1',
        ];

        $first = $this->postJson("/api/v1/employees/{$employeeId}/deductions", [
            'deduction_type' => 'staff_meal',
            'amount' => 250.50,
            'source_reference' => 'CONSUMPTION-1',
        ], $headers);

        $first->assertCreated()
            ->assertJsonPath('data.amount', '250.50');

        $second = $this->postJson("/api/v1/employees/{$employeeId}/deductions", [
            'deduction_type' => 'staff_meal',
            'amount' => 250.50,
        ], $headers);

        $second->assertCreated()
            ->assertJsonPath('data.id', $first->json('data.id'));

        $this->assertDatabaseCount('employee_deductions', 1);
    }
}
