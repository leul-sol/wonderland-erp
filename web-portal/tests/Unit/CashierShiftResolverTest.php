<?php

namespace Tests\Unit;

use App\Exceptions\ApiException;
use App\Services\Api\S3HospitalityClient;
use App\Services\Auth\PortalAuthService;
use App\Services\FrontDesk\CashierShiftResolver;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class CashierShiftResolverTest extends TestCase
{
    public function test_returns_open_shift_for_current_user(): void
    {
        $this->mock(PortalAuthService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('user')->andReturn(['id' => 42]);
            $mock->shouldReceive('hasAnyPermission')->with(['S3.hotel.cashier.read'])->andReturn(true);
        });

        $this->mock(S3HospitalityClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('cashierShifts')
                ->once()
                ->with([
                    'cashier_id' => 42,
                    'status' => 'open',
                    'per_page' => 5,
                ])
                ->andReturn([
                    'data' => [
                        'data' => [
                            ['id' => 7, 'status' => 'open', 'opening_cash_float' => '1000.00'],
                        ],
                    ],
                ]);
        });

        $shift = app(CashierShiftResolver::class)->openShiftForCurrentUser();

        $this->assertSame(7, $shift['id']);
        $this->assertSame(7, app(CashierShiftResolver::class)->openShiftIdForCurrentUser());
    }

    public function test_skips_api_when_user_cannot_read_shifts(): void
    {
        $this->mock(PortalAuthService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('user')->andReturn(['id' => 42]);
            $mock->shouldReceive('hasAnyPermission')->with(['S3.hotel.cashier.read'])->andReturn(false);
        });

        $this->mock(S3HospitalityClient::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('cashierShifts');
        });

        $this->assertNull(app(CashierShiftResolver::class)->openShiftForCurrentUser());
    }

    public function test_uses_property_shift_for_cash_collection_when_user_has_no_shift(): void
    {
        $this->mock(PortalAuthService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('user')->andReturn(['id' => 42]);
            $mock->shouldReceive('hasAnyPermission')->with(['S3.hotel.cashier.read'])->andReturn(true);
        });

        $this->mock(S3HospitalityClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('cashierShifts')
                ->once()
                ->with([
                    'cashier_id' => 42,
                    'status' => 'open',
                    'per_page' => 5,
                ])
                ->andReturn(['data' => ['data' => []]]);

            $mock->shouldReceive('cashierShifts')
                ->once()
                ->with([
                    'status' => 'open',
                    'per_page' => 5,
                ])
                ->andReturn([
                    'data' => [
                        'data' => [
                            ['id' => 9, 'status' => 'open'],
                        ],
                    ],
                ]);
        });

        $this->assertSame(9, app(CashierShiftResolver::class)->openShiftIdForCashCollection());
    }
}
