<?php

namespace Tests\Feature;

use App\Models\RtmEntry;
use App\Models\UatScenario;
use Database\Seeders\AccountSeeder;
use Database\Seeders\FiscalPeriodSeeder;
use Database\Seeders\RtmSeeder;
use Database\Seeders\UatSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MocksS4Auth;
use Tests\TestCase;

class Phase5Test extends TestCase
{
    use MocksS4Auth;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.internal_key_current' => 'test-service-key']);

        $this->seed([
            AccountSeeder::class,
            FiscalPeriodSeeder::class,
            RtmSeeder::class,
            UatSeeder::class,
        ]);
    }

    public function test_rtm_index_returns_catalog_with_summary(): void
    {
        $response = $this->getJson('/api/v1/bi/rtm', $this->authHeaders());

        $response->assertOk()
            ->assertJsonPath('meta.total', 20)
            ->assertJsonStructure(['data' => [['requirement_key', 'system', 'status']], 'meta' => ['coverage_percent', 'by_status']]);
    }

    public function test_rtm_can_be_filtered_by_system(): void
    {
        $response = $this->getJson('/api/v1/bi/rtm?system=S3', $this->authHeaders());

        $response->assertOk();
        $this->assertTrue(collect($response->json('data'))->every(fn ($row) => $row['system'] === 'S3'));
    }

    public function test_rtm_update_succeeds_with_permission(): void
    {
        $entry = RtmEntry::query()->where('requirement_key', 'S2-LEAVE-001')->firstOrFail();

        $response = $this->patchJson("/api/v1/bi/rtm/{$entry->id}", [
            'status' => 'in_progress',
            'notes' => 'Starting leave module',
        ], $this->authHeaders());

        $response->assertOk()
            ->assertJsonPath('data.status', 'in_progress')
            ->assertJsonPath('data.updated_by', 1);
    }

    public function test_rtm_update_forbidden_without_update_permission(): void
    {
        $entry = RtmEntry::query()->where('requirement_key', 'S2-LEAVE-001')->firstOrFail();

        $this->patchJson("/api/v1/bi/rtm/{$entry->id}", [
            'status' => 'in_progress',
        ], $this->authHeaders(['S4.bi.rtm.read']))->assertStatus(403);
    }

    public function test_uat_index_returns_scenarios_with_pass_rate(): void
    {
        $response = $this->getJson('/api/v1/bi/uat', $this->authHeaders());

        $response->assertOk()
            ->assertJsonPath('meta.total', 10)
            ->assertJsonStructure(['meta' => ['pass_rate_percent', 'by_status']]);
    }

    public function test_uat_record_result_updates_scenario(): void
    {
        $scenario = UatScenario::query()->where('scenario_key', 'UAT-S4-001')->firstOrFail();
        $requirement = RtmEntry::query()->where('requirement_key', 'S4-RPT-001')->firstOrFail();
        $requirement->update(['status' => 'implemented']);

        $response = $this->postJson("/api/v1/bi/uat/{$scenario->id}/results", [
            'status' => 'passed',
            'notes' => 'Trial balance balanced in UAT',
        ], $this->authHeaders());

        $response->assertOk()
            ->assertJsonPath('data.status', 'passed')
            ->assertJsonPath('data.executed_by', 1);

        $requirement->refresh();
        $this->assertSame('verified', $requirement->status);
        $this->assertNotNull($requirement->verified_at);
    }

    public function test_uat_record_forbidden_without_update_permission(): void
    {
        $scenario = UatScenario::query()->firstOrFail();

        $this->postJson("/api/v1/bi/uat/{$scenario->id}/results", [
            'status' => 'failed',
        ], $this->authHeaders(['S4.bi.uat.read']))->assertStatus(403);
    }
}
