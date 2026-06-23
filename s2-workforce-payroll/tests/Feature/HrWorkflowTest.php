<?php

namespace Tests\Feature;

use App\Models\AssetType;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\MocksS2Auth;
use Tests\Concerns\SeedsPayrollAttendance;
use Tests\TestCase;

class HrWorkflowTest extends TestCase
{
    use MocksS2Auth;
    use RefreshDatabase;
    use SeedsPayrollAttendance;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.internal_key_current' => 'test-service-key']);
        Storage::fake('local');
        $this->seed(DatabaseSeeder::class);
    }

    public function test_department_crud(): void
    {
        $headers = $this->authHeaders();

        $created = $this->postJson('/api/v1/departments', [
            'code' => 'IT',
            'name' => 'Information Technology',
        ], $headers)->assertCreated();

        $departmentId = $created->json('data.id');

        $this->getJson('/api/v1/departments', $headers)
            ->assertOk()
            ->assertJsonFragment(['code' => 'IT']);

        $this->patchJson("/api/v1/departments/{$departmentId}", [
            'name' => 'IT Department',
        ], $headers)->assertOk()
            ->assertJsonPath('data.name', 'IT Department');
    }

    public function test_suspension_disciplinary_action_sets_employee_suspended(): void
    {
        $headers = $this->authHeaders();

        $employeeId = $this->postJson('/api/v1/employees', [
            'full_name' => 'Suspended Staff',
            'base_salary' => 15000,
        ], $headers)->json('data.id');

        $this->postJson("/api/v1/employees/{$employeeId}/disciplinary-records", [
            'action_type' => 'suspension',
            'reason' => 'Policy breach',
            'effective_date' => now()->toDateString(),
            'suspension_days' => 5,
        ], $headers)->assertCreated();

        $this->getJson("/api/v1/employees/{$employeeId}", $headers)
            ->assertJsonPath('data.status', 'suspended');
    }

    public function test_asset_assign_return_and_offboarding_clearance_gate(): void
    {
        $headers = $this->authHeaders();
        $assetTypeId = AssetType::query()->firstOrFail()->id;

        $employeeId = $this->postJson('/api/v1/employees', [
            'full_name' => 'Asset Holder',
            'base_salary' => 15000,
        ], $headers)->json('data.id');

        $assetId = $this->postJson("/api/v1/employees/{$employeeId}/assets", [
            'asset_type_id' => $assetTypeId,
            'serial_number' => 'LAP-001',
        ], $headers)->assertCreated()->json('data.id');

        $offboardingId = $this->postJson("/api/v1/employees/{$employeeId}/offboarding", [
            'reason' => 'resignation',
            'last_working_day' => now()->addWeek()->toDateString(),
        ], $headers)->assertCreated()->json('data.id');

        $this->patchJson("/api/v1/offboarding-records/{$offboardingId}", [
            'clearance_status' => 'completed',
        ], $headers)->assertStatus(422);

        $this->putJson("/api/v1/assets/{$assetId}/return", [
            'condition_on_return' => 'good',
        ], $headers)->assertOk();

        $this->patchJson("/api/v1/offboarding-records/{$offboardingId}", [
            'clearance_status' => 'completed',
        ], $headers)->assertOk();
    }

    public function test_guarantor_registration_generates_pdf_letter(): void
    {
        $headers = $this->authHeaders();

        $employeeId = $this->postJson('/api/v1/employees', [
            'full_name' => 'Guaranteed Staff',
            'base_salary' => 12000,
        ], $headers)->json('data.id');

        $guarantorId = $this->postJson("/api/v1/employees/{$employeeId}/guarantors", [
            'full_name' => 'Abebe Kebede',
            'national_id' => 'ID-12345',
            'phone' => '0911000000',
            'address' => 'Addis Ababa',
            'relationship' => 'Brother',
        ], $headers)->assertCreated()->json('data.id');

        $letterPath = $this->getJson("/api/v1/employees/{$employeeId}/guarantors", $headers)
            ->json('data.0.letter_path');

        $this->assertNotNull($letterPath);
        Storage::disk('local')->assertExists($letterPath);

        $this->get("/api/v1/employees/{$employeeId}/guarantors/{$guarantorId}/letter", $headers)
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_payslip_pdf_for_approved_payroll_run(): void
    {
        Http::fake([
            '*/api/v1/journal-entries' => Http::response(['data' => ['id' => 77]], 201),
        ]);

        $headers = $this->authHeaders();
        $employeeId = $this->postJson('/api/v1/employees', [
            'full_name' => 'Payslip Staff',
            'base_salary' => 18000,
        ], $headers)->json('data.id');

        $this->seedWeekdayAttendance($employeeId, '2026-06-01', '2026-06-30');

        $runId = $this->postJson('/api/v1/payroll-runs', [
            'period_start' => '2026-06-01',
            'period_end' => '2026-06-30',
        ], $headers)->json('data.id');

        $this->postJson("/api/v1/payroll-runs/{$runId}/submit", [], $headers)->assertOk();
        $this->postJson("/api/v1/payroll-runs/{$runId}/approve", [], array_merge($headers, [
            'Idempotency-Key' => 'payslip-test-'.$runId,
        ]))->assertOk();

        $this->get("/api/v1/employees/{$employeeId}/payslip/{$runId}", $headers)
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }
}
