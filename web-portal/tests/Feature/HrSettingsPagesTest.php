<?php

namespace Tests\Feature;

use App\Services\Api\S2WorkforceClient;
use Illuminate\Support\Facades\Session;
use Mockery\MockInterface;
use Tests\TestCase;

class HrSettingsPagesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Session::put('portal.access_token', 'test-token');
        Session::put('portal.permissions', [
            'S2.workforce.leave_types.read',
            'S2.workforce.overtime.read',
            'S2.workforce.overtime.update',
            'S2.hr.assets.read',
            'S2.hr.assets.write',
        ]);
    }

    public function test_settings_index_renders(): void
    {
        $this->mock(S2WorkforceClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('leaveTypes')->once()->andReturn([
                'data' => [['id' => 1, 'code' => 'ANNUAL', 'name' => 'Annual', 'max_days_per_year' => 14, 'paid' => true]],
            ]);
            $mock->shouldReceive('overtimeRates')->once()->andReturn([
                'data' => [['id' => 2, 'category' => 'working_day', 'multiplier' => '1.50']],
            ]);
            $mock->shouldReceive('assetTypes')->once()->andReturn([
                'data' => [['id' => 3, 'name' => 'Laptop', 'description' => 'IT equipment']],
            ]);
        });

        $response = $this->get('/hr/settings');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Hr/Settings/Index')
            ->has('leaveTypes', 1)
            ->has('overtimeRates', 1)
            ->has('assetTypes', 1)
            ->where('canUpdateOvertimeRates', true));
    }

    public function test_overtime_rate_update_posts_to_s2(): void
    {
        $this->withoutMiddleware();

        $this->mock(S2WorkforceClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('updateOvertimeRate')->once()->with(4, ['multiplier' => 1.75])->andReturn([
                'data' => ['id' => 4, 'category' => 'night', 'multiplier' => '1.75'],
            ]);
        });

        $response = $this->patch('/hr/settings/overtime-rates/4', ['multiplier' => 1.75]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_asset_type_store_posts_to_s2(): void
    {
        $this->withoutMiddleware();

        $this->mock(S2WorkforceClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('createAssetType')->once()->with([
                'name' => 'Phone',
                'description' => 'Mobile handset',
            ])->andReturn(['data' => ['id' => 8]]);
        });

        $response = $this->post('/hr/settings/asset-types', [
            'name' => 'Phone',
            'description' => 'Mobile handset',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }
}
