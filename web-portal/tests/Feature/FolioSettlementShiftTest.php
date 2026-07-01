<?php

namespace Tests\Feature;

use App\Services\Api\S3HospitalityClient;
use Illuminate\Support\Facades\Session;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class FolioSettlementShiftTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware();

        Session::put('portal.access_token', 'test-token');
        Session::put('portal.user', ['id' => 9, 'username' => 'cashier.mulatu']);
        Session::put('portal.permissions', [
            'S3.hotel.folios.read',
            'S3.hotel.folios.write',
            'S3.hotel.cashier.read',
        ]);
    }

    public function test_folio_settle_posts_payment_with_open_shift(): void
    {
        $this->mock(S3HospitalityClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('cashierShifts')
                ->once()
                ->with([
                    'cashier_id' => 9,
                    'status' => 'open',
                    'per_page' => 5,
                ])
                ->andReturn([
                    'data' => [
                        'data' => [[
                            'id' => 3,
                            'status' => 'open',
                        ]],
                    ],
                ]);

            $mock->shouldReceive('recordFolioPayment')
                ->once()
                ->with(8, Mockery::on(function (array $payload): bool {
                    return $payload['amount'] === 3162.5
                        && $payload['payment_method'] === 'cash'
                        && $payload['cashier_shift_id'] === 3;
                }), Mockery::type('string'))
                ->andReturn(['data' => ['id' => 1]]);
        });

        $response = $this->post('/front-desk/folios/8/settle', [
            'amount' => '3162.50',
            'payment_method' => 'cash',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_folio_settle_cash_without_shift_returns_validation_error(): void
    {
        $this->mock(S3HospitalityClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('cashierShifts')
                ->once()
                ->andReturn(['data' => ['data' => []]]);
        });

        $response = $this->from('/front-desk/folios/8')
            ->post('/front-desk/folios/8/settle', [
                'amount' => '100.00',
                'payment_method' => 'cash',
            ]);

        $response->assertRedirect('/front-desk/folios/8');
        $response->assertSessionHasErrors('payment_method');
    }
}
