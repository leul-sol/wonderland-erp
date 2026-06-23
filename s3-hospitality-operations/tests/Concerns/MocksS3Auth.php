<?php

namespace Tests\Concerns;

trait MocksS3Auth
{
    private bool $s3AuthMocked = false;

    /**
     * @param  list<string>  $permissions
     * @return array<string, string>
     */
    protected function authHeaders(array $permissions = [], array $roles = ['super_admin']): array
    {
        $defaults = [
            'S3.inventory.items.read',
            'S3.inventory.items.write',
            'S3.inventory.stock.read',
            'S3.inventory.stock.write',
            'S3.inventory.suppliers.read',
            'S3.inventory.suppliers.write',
            'S3.inventory.purchase_orders.read',
            'S3.inventory.purchase_orders.write',
            'S3.inventory.purchase_orders.approve',
            'S3.inventory.reports.read',
            'S3.restaurant.menu.read',
            'S3.restaurant.menu.write',
            'S3.restaurant.orders.read',
            'S3.restaurant.orders.write',
            'S3.restaurant.billing.write',
            'S3.restaurant.consumption.read',
            'S3.restaurant.consumption.write',
            'S3.hotel.rooms.read',
            'S3.hotel.rooms.write',
            'S3.hotel.guests.read',
            'S3.hotel.guests.write',
            'S3.hotel.reservations.read',
            'S3.hotel.reservations.write',
            'S3.hotel.checkinout.write',
            'S3.hotel.folios.read',
            'S3.hotel.folios.write',
            'S3.hotel.cashier.read',
            'S3.hotel.cashier.write',
            'S3.hotel.group_bookings.read',
            'S3.hotel.group_bookings.create',
            'S3.hotel.group_bookings.check_in',
            'S3.hotel.group_bookings.check_out',
        ];

        if (! $this->s3AuthMocked) {
            $this->mock(\App\Services\S1AuthService::class, function ($mock) use ($permissions, $defaults, $roles) {
                $mock->shouldReceive('verify')->andReturn([
                    'valid' => true,
                    'user' => [
                        'sub' => 1,
                        'permissions' => $permissions === [] ? $defaults : $permissions,
                        'roles' => $roles,
                    ],
                ]);
            });
            $this->s3AuthMocked = true;
        }

        return ['Authorization' => 'Bearer test-token'];
    }

    /**
     * @param  array<string, string>  $headers
     * @return array<string, string>
     */
    protected function withIdempotency(array $headers, string $key): array
    {
        return array_merge($headers, ['Idempotency-Key' => $key]);
    }
}
