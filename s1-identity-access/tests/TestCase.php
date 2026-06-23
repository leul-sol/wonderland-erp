<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'seeding.super_admin_password' => env('SUPER_ADMIN_PASSWORD', 'ChangeMeNow!10'),
            'seeding.admin_must_change_password' => false,
        ]);
    }

    protected function superAdminPassword(): string
    {
        return (string) config('seeding.super_admin_password');
    }
}
