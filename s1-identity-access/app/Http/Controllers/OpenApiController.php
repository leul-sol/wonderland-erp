<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

class OpenApiController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'openapi' => '3.0.3',
            'info' => [
                'title' => 'Wonderland S1 Identity & Access',
                'version' => '1.0',
            ],
            'servers' => [
                ['url' => 'http://localhost/s1/api/v1'],
            ],
            'paths' => [
                '/health' => ['get' => ['summary' => 'Health check']],
                '/auth/login' => ['post' => ['summary' => 'Login']],
                '/auth/refresh' => ['post' => ['summary' => 'Refresh tokens']],
                '/auth/verify' => ['post' => ['summary' => 'Verify JWT (service key)']],
                '/auth/logout' => ['post' => ['summary' => 'Logout']],
                '/auth/me' => ['get' => ['summary' => 'Current user']],
                '/auth/change-password' => ['post' => ['summary' => 'Change password']],
                '/auth/jwks' => ['get' => ['summary' => 'JWKS']],
                '/users' => ['get' => ['summary' => 'List users'], 'post' => ['summary' => 'Create user']],
                '/users/{id}' => ['get' => ['summary' => 'Get user'], 'put' => ['summary' => 'Update user'], 'delete' => ['summary' => 'Delete user']],
                '/users/{id}/deactivate' => ['post' => ['summary' => 'Deactivate user']],
                '/users/{id}/force-logout' => ['post' => ['summary' => 'Force logout user']],
                '/users/{id}/reset-password' => ['post' => ['summary' => 'Reset password']],
                '/users/{id}/roles' => ['post' => ['summary' => 'Assign roles'], 'put' => ['summary' => 'Sync roles']],
                '/users/{id}/roles/{rid}' => ['delete' => ['summary' => 'Remove role']],
                '/roles' => ['get' => ['summary' => 'List roles'], 'post' => ['summary' => 'Create role']],
                '/roles/{id}' => ['get' => ['summary' => 'Get role'], 'put' => ['summary' => 'Update role'], 'delete' => ['summary' => 'Delete role']],
                '/roles/{id}/permissions' => ['post' => ['summary' => 'Sync permissions'], 'put' => ['summary' => 'Sync permissions']],
                '/permissions' => ['get' => ['summary' => 'List permissions']],
                '/permissions/{domain}' => ['get' => ['summary' => 'List permissions by domain']],
                '/audit-logs' => ['get' => ['summary' => 'List audit logs']],
                '/audit-logs/user/{id}' => ['get' => ['summary' => 'Audit logs for user']],
            ],
            'components' => [
                'securitySchemes' => [
                    'bearerAuth' => ['type' => 'http', 'scheme' => 'bearer', 'bearerFormat' => 'JWT'],
                    'serviceKey' => ['type' => 'apiKey', 'in' => 'header', 'name' => 'X-Service-Key'],
                ],
            ],
        ]);
    }
}
