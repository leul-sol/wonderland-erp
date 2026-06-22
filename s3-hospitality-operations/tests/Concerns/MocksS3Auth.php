<?php

namespace Tests\Concerns;

trait MocksS3Auth
{
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
        ];

        $this->mock(\App\Services\S1AuthService::class, function ($mock) use ($permissions, $defaults) {
            $mock->shouldReceive('verify')->andReturn([
                'valid' => true,
                'user' => [
                    'sub' => 1,
                    'permissions' => $permissions === [] ? $defaults : $permissions,
                    'roles' => ['receptionist'],
                ],
            ]);
        });

        return ['Authorization' => 'Bearer test-token'];
    }
}
