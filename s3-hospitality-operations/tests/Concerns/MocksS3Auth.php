<?php

namespace Tests\Concerns;

trait MocksS3Auth
{
    private bool $s3AuthMocked = false;

    /**
     * @param  list<string>  $permissions
     * @return array<string, string>
     */
    protected function authHeaders(array $permissions = []): array
    {
        $defaults = [
            'S3.hospitality.rooms.read',
            'S3.hospitality.reservations.read',
            'S3.hospitality.reservations.create',
            'S3.hospitality.reservations.check_in',
            'S3.hospitality.reservations.check_out',
            'S3.hospitality.folios.read',
            'S3.hospitality.folios.charge',
            'S3.hospitality.folios.settle',
            'S3.hospitality.items.read',
            'S3.hospitality.purchase_orders.read',
            'S3.hospitality.purchase_orders.create',
            'S3.hospitality.purchase_orders.approve',
            'S3.hospitality.purchase_orders.receive',
            'S3.hospitality.menu_items.read',
            'S3.hospitality.orders.read',
            'S3.hospitality.orders.create',
            'S3.hospitality.orders.finalize',
            'S3.hospitality.consumption.read',
            'S3.hospitality.consumption.create',
            'S3.hospitality.consumption.close',
            'S3.hospitality.group_bookings.read',
            'S3.hospitality.group_bookings.create',
            'S3.hospitality.group_bookings.check_in',
            'S3.hospitality.group_bookings.check_out',
        ];

        if (! $this->s3AuthMocked) {
            $this->mock(\App\Services\S1AuthService::class, function ($mock) use ($permissions, $defaults) {
                $mock->shouldReceive('verify')->andReturn([
                    'valid' => true,
                    'user' => [
                        'sub' => 1,
                        'permissions' => $permissions === [] ? $defaults : $permissions,
                        'roles' => ['restaurant_manager'],
                    ],
                ]);
            });
            $this->s3AuthMocked = true;
        }

        return ['Authorization' => 'Bearer test-token'];
    }
}
