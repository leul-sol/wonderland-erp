<?php

namespace App\Support;

class OpenApiDocument
{
    /**
     * @return array<string, mixed>
     */
    public static function build(): array
    {
        return [
            'openapi' => '3.0.3',
            'info' => [
                'title' => 'Wonderland S1 Identity & Access',
                'version' => '1.0',
                'description' => 'Identity, authentication, and RBAC for Wonderland Hotel ERP.',
            ],
            'servers' => [
                ['url' => 'http://localhost/s1/api/v1'],
            ],
            'paths' => self::paths(),
            'components' => [
                'securitySchemes' => [
                    'bearerAuth' => ['type' => 'http', 'scheme' => 'bearer', 'bearerFormat' => 'JWT'],
                    'serviceKey' => ['type' => 'apiKey', 'in' => 'header', 'name' => 'X-Service-Key'],
                ],
                'schemas' => self::schemas(),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function paths(): array
    {
        $error = ['$ref' => '#/components/schemas/ErrorResponse'];
        $errorContent = ['application/json' => ['schema' => $error]];
        $verifyFailureContent = ['application/json' => ['schema' => ['$ref' => '#/components/schemas/VerifyFailureResponse']]];
        $bearer = [['bearerAuth' => []]];
        $bearerOrService = [['bearerAuth' => []], ['serviceKey' => []]];

        return [
            '/health' => [
                'get' => [
                    'summary' => 'Health check',
                    'responses' => ['200' => ['description' => 'OK', 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/HealthResponse']]]]],
                ],
            ],
            '/auth/login' => [
                'post' => [
                    'summary' => 'Login',
                    'requestBody' => ['required' => true, 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/LoginRequest']]]],
                    'responses' => [
                        '200' => ['description' => 'Authenticated', 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/TokenResponse']]]],
                        '401' => ['description' => 'Invalid credentials', 'content' => $errorContent],
                        '403' => ['description' => 'Locked or deactivated', 'content' => $errorContent],
                    ],
                ],
            ],
            '/auth/refresh' => [
                'post' => [
                    'summary' => 'Refresh tokens',
                    'requestBody' => ['required' => true, 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/RefreshRequest']]]],
                    'responses' => [
                        '200' => ['description' => 'New tokens', 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/TokenResponse']]]],
                        '401' => ['description' => 'Invalid refresh token', 'content' => $errorContent],
                    ],
                ],
            ],
            '/auth/verify' => [
                'post' => [
                    'summary' => 'Verify JWT (service key)',
                    'security' => [['serviceKey' => []]],
                    'parameters' => [['name' => 'Authorization', 'in' => 'header', 'required' => true, 'schema' => ['type' => 'string']]],
                    'responses' => [
                        '200' => ['description' => 'Verification result', 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/VerifyResponse']]]],
                        '401' => ['description' => 'Invalid or missing credentials', 'content' => $verifyFailureContent],
                    ],
                ],
            ],
            '/auth/logout' => [
                'post' => ['summary' => 'Logout', 'security' => $bearer, 'responses' => ['200' => ['description' => 'Logged out', 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/MessageResponse']]]]]],
            ],
            '/auth/me' => [
                'get' => ['summary' => 'Current user', 'security' => $bearer, 'responses' => ['200' => ['description' => 'Profile', 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/MeResponse']]]]]],
            ],
            '/auth/change-password' => [
                'post' => [
                    'summary' => 'Change password',
                    'security' => $bearer,
                    'requestBody' => ['required' => true, 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/ChangePasswordRequest']]]],
                    'responses' => ['200' => ['description' => 'Updated', 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/MessageResponse']]]]],
                ],
            ],
            '/auth/jwks' => [
                'get' => ['summary' => 'JWKS', 'responses' => ['200' => ['description' => 'Public keys', 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/JwksResponse']]]]]],
            ],
            '/users' => [
                'get' => [
                    'summary' => 'List users',
                    'security' => $bearerOrService,
                    'responses' => ['200' => ['description' => 'Paged users', 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/UserListResponse']]]]],
                ],
                'post' => [
                    'summary' => 'Create user',
                    'security' => $bearer,
                    'requestBody' => ['required' => true, 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/StoreUserRequest']]]],
                    'responses' => ['201' => ['description' => 'Created', 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/UserResponse']]]]],
                ],
            ],
            '/users/{id}' => [
                'get' => ['summary' => 'Get user', 'security' => $bearerOrService, 'responses' => ['200' => ['description' => 'User', 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/UserResponse']]]]]],
                'put' => ['summary' => 'Update user', 'security' => $bearer, 'responses' => ['200' => ['description' => 'Updated', 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/UserResponse']]]]]],
                'delete' => ['summary' => 'Delete user', 'security' => $bearer, 'responses' => ['204' => ['description' => 'Deleted']]],
            ],
            '/users/{id}/deactivate' => [
                'post' => [
                    'summary' => 'Deactivate user',
                    'security' => $bearer,
                    'responses' => ['200' => ['description' => 'Deactivated', 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/UserResponse']]]]],
                ],
            ],
            '/users/{id}/force-logout' => [
                'post' => [
                    'summary' => 'Revoke all refresh tokens for user',
                    'security' => $bearer,
                    'responses' => ['200' => ['description' => 'Sessions revoked', 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/MessageResponse']]]]],
                ],
            ],
            '/users/{id}/reset-password' => [
                'post' => [
                    'summary' => 'Admin reset user password',
                    'security' => $bearer,
                    'requestBody' => ['required' => true, 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/ResetUserPasswordRequest']]]],
                    'responses' => ['200' => ['description' => 'Password reset', 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/MessageResponse']]]]],
                ],
            ],
            '/users/{id}/roles' => [
                'put' => [
                    'summary' => 'Replace user role assignments',
                    'security' => $bearer,
                    'requestBody' => ['required' => true, 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/AssignUserRolesRequest']]]],
                    'responses' => ['200' => ['description' => 'Roles assigned', 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/UserResponse']]]]],
                ],
                'post' => [
                    'summary' => 'Replace user role assignments (alias)',
                    'security' => $bearer,
                    'requestBody' => ['required' => true, 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/AssignUserRolesRequest']]]],
                    'responses' => ['200' => ['description' => 'Roles assigned', 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/UserResponse']]]]],
                ],
            ],
            '/users/{id}/roles/{roleId}' => [
                'delete' => [
                    'summary' => 'Remove one role from user',
                    'security' => $bearer,
                    'responses' => ['200' => ['description' => 'Role removed', 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/UserResponse']]]]],
                ],
            ],
            '/roles' => [
                'get' => ['summary' => 'List roles', 'security' => $bearerOrService, 'responses' => ['200' => ['description' => 'Roles', 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/RoleListResponse']]]]]],
                'post' => ['summary' => 'Create role', 'security' => $bearer, 'responses' => ['201' => ['description' => 'Created', 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/RoleResponse']]]]]],
            ],
            '/roles/{id}' => [
                'get' => ['summary' => 'Get role', 'security' => $bearerOrService, 'responses' => ['200' => ['description' => 'Role', 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/RoleResponse']]]]]],
                'put' => ['summary' => 'Update role', 'security' => $bearer, 'responses' => ['200' => ['description' => 'Updated', 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/RoleResponse']]]]]],
                'delete' => ['summary' => 'Delete custom role', 'security' => $bearer, 'responses' => ['204' => ['description' => 'Deleted']]],
            ],
            '/roles/{id}/permissions' => [
                'put' => ['summary' => 'Sync role permissions', 'security' => $bearer, 'responses' => ['200' => ['description' => 'Synced', 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/RoleResponse']]]]]],
            ],
            '/permissions' => [
                'get' => ['summary' => 'List permissions', 'security' => $bearerOrService, 'responses' => ['200' => ['description' => 'Permissions', 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/PermissionListResponse']]]]]],
            ],
            '/permissions/{domain}' => [
                'get' => ['summary' => 'List permissions by domain', 'security' => $bearerOrService, 'responses' => ['200' => ['description' => 'Permissions', 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/PermissionListResponse']]]]]],
            ],
            '/audit-logs' => [
                'get' => ['summary' => 'List audit logs', 'security' => $bearerOrService, 'responses' => ['200' => ['description' => 'Audit events', 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/AuditLogListResponse']]]]]],
            ],
            '/audit-logs/user/{id}' => [
                'get' => ['summary' => 'Audit logs for user', 'security' => $bearerOrService, 'responses' => ['200' => ['description' => 'Audit events', 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/AuditLogListResponse']]]]]],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function schemas(): array
    {
        return [
            'ErrorResponse' => [
                'type' => 'object',
                'properties' => [
                    'error' => [
                        'type' => 'object',
                        'properties' => [
                            'code' => ['type' => 'string'],
                            'message' => ['type' => 'string'],
                            'details' => ['type' => 'object'],
                        ],
                        'required' => ['code', 'message'],
                    ],
                ],
            ],
            'HealthResponse' => [
                'type' => 'object',
                'properties' => [
                    'status' => ['type' => 'string', 'example' => 'ok'],
                    'system' => ['type' => 'string', 'example' => 'S1'],
                    'version' => ['type' => 'string'],
                ],
            ],
            'LoginRequest' => [
                'type' => 'object',
                'required' => ['username', 'password'],
                'properties' => [
                    'username' => ['type' => 'string'],
                    'password' => ['type' => 'string', 'format' => 'password'],
                ],
            ],
            'RefreshRequest' => [
                'type' => 'object',
                'required' => ['refresh_token'],
                'properties' => ['refresh_token' => ['type' => 'string']],
            ],
            'TokenResponse' => [
                'type' => 'object',
                'properties' => [
                    'access_token' => ['type' => 'string'],
                    'refresh_token' => ['type' => 'string'],
                    'token_type' => ['type' => 'string', 'example' => 'Bearer'],
                    'expires_in' => ['type' => 'integer'],
                    'must_change_password' => ['type' => 'boolean'],
                ],
            ],
            'VerifyResponse' => [
                'type' => 'object',
                'properties' => [
                    'valid' => ['type' => 'boolean', 'example' => true],
                    'user' => ['$ref' => '#/components/schemas/VerifiedUser'],
                ],
            ],
            'VerifyFailureResponse' => [
                'type' => 'object',
                'properties' => [
                    'valid' => ['type' => 'boolean', 'example' => false],
                    'reason' => ['type' => 'string'],
                ],
            ],
            'VerifiedUser' => [
                'type' => 'object',
                'properties' => [
                    'sub' => ['type' => 'integer'],
                    'username' => ['type' => 'string'],
                    'name' => ['type' => 'string', 'nullable' => true],
                    'employee_id' => ['type' => 'integer', 'nullable' => true],
                    'roles' => ['type' => 'array', 'items' => ['type' => 'string']],
                    'permissions' => ['type' => 'array', 'items' => ['type' => 'string']],
                    'dept_scope' => ['type' => 'integer', 'nullable' => true],
                    'must_change_password' => ['type' => 'boolean'],
                ],
            ],
            'MeResponse' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer'],
                    'username' => ['type' => 'string'],
                    'email' => ['type' => 'string', 'nullable' => true],
                    'name' => ['type' => 'string'],
                    'employee_id' => ['type' => 'integer', 'nullable' => true],
                    'roles' => ['type' => 'array', 'items' => ['type' => 'string']],
                    'permissions' => ['type' => 'array', 'items' => ['type' => 'string']],
                    'dept_scope' => ['type' => 'integer', 'nullable' => true],
                    'must_change_password' => ['type' => 'boolean'],
                ],
            ],
            'ChangePasswordRequest' => [
                'type' => 'object',
                'required' => ['current_password', 'password', 'password_confirmation'],
                'properties' => [
                    'current_password' => ['type' => 'string'],
                    'password' => ['type' => 'string'],
                    'password_confirmation' => ['type' => 'string'],
                ],
            ],
            'MessageResponse' => [
                'type' => 'object',
                'properties' => ['message' => ['type' => 'string']],
            ],
            'JwksResponse' => [
                'type' => 'object',
                'properties' => [
                    'keys' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'properties' => [
                                'kty' => ['type' => 'string'],
                                'kid' => ['type' => 'string'],
                                'use' => ['type' => 'string'],
                                'alg' => ['type' => 'string'],
                                'n' => ['type' => 'string'],
                                'e' => ['type' => 'string'],
                            ],
                        ],
                    ],
                ],
            ],
            'StoreUserRequest' => [
                'type' => 'object',
                'required' => ['username', 'password', 'display_name'],
                'properties' => [
                    'username' => ['type' => 'string'],
                    'email' => ['type' => 'string', 'nullable' => true],
                    'password' => ['type' => 'string'],
                    'display_name' => ['type' => 'string'],
                    'employee_id' => ['type' => 'integer', 'nullable' => true],
                    'role_ids' => ['type' => 'array', 'items' => ['type' => 'integer']],
                ],
            ],
            'ResetUserPasswordRequest' => [
                'type' => 'object',
                'required' => ['password'],
                'properties' => [
                    'password' => ['type' => 'string'],
                    'must_change_password' => ['type' => 'boolean'],
                ],
            ],
            'AssignUserRolesRequest' => [
                'type' => 'object',
                'required' => ['roles'],
                'properties' => [
                    'roles' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'required' => ['role_id'],
                            'properties' => [
                                'role_id' => ['type' => 'integer'],
                                'department_id' => ['type' => 'integer', 'nullable' => true],
                            ],
                        ],
                    ],
                ],
            ],
            'User' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer'],
                    'username' => ['type' => 'string'],
                    'email' => ['type' => 'string', 'nullable' => true],
                    'display_name' => ['type' => 'string'],
                    'employee_id' => ['type' => 'integer', 'nullable' => true],
                    'is_active' => ['type' => 'boolean'],
                    'must_change_password' => ['type' => 'boolean'],
                    'roles' => ['type' => 'array', 'items' => ['$ref' => '#/components/schemas/RoleSummary']],
                ],
            ],
            'UserResponse' => [
                'type' => 'object',
                'properties' => ['data' => ['$ref' => '#/components/schemas/User']],
            ],
            'UserListResponse' => [
                'type' => 'object',
                'properties' => [
                    'data' => ['type' => 'array', 'items' => ['$ref' => '#/components/schemas/User']],
                    'meta' => ['$ref' => '#/components/schemas/ListMeta'],
                ],
            ],
            'RoleSummary' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer'],
                    'name' => ['type' => 'string'],
                    'display_name' => ['type' => 'string'],
                ],
            ],
            'Role' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer'],
                    'name' => ['type' => 'string'],
                    'display_name' => ['type' => 'string'],
                    'is_system' => ['type' => 'boolean'],
                    'permissions' => ['type' => 'array', 'items' => ['$ref' => '#/components/schemas/Permission']],
                ],
            ],
            'RoleResponse' => [
                'type' => 'object',
                'properties' => ['data' => ['$ref' => '#/components/schemas/Role']],
            ],
            'RoleListResponse' => [
                'type' => 'object',
                'properties' => [
                    'data' => ['type' => 'array', 'items' => ['$ref' => '#/components/schemas/Role']],
                    'meta' => ['$ref' => '#/components/schemas/ListMeta'],
                ],
            ],
            'Permission' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer'],
                    'domain' => ['type' => 'string'],
                    'action' => ['type' => 'string'],
                    'display_name' => ['type' => 'string'],
                ],
            ],
            'PermissionListResponse' => [
                'type' => 'object',
                'properties' => [
                    'data' => ['type' => 'array', 'items' => ['$ref' => '#/components/schemas/Permission']],
                    'meta' => ['$ref' => '#/components/schemas/ListMeta'],
                ],
            ],
            'AuditLog' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer'],
                    'user_id' => ['type' => 'integer', 'nullable' => true],
                    'event' => ['type' => 'string'],
                    'ip_address' => ['type' => 'string'],
                    'payload' => ['type' => 'object', 'nullable' => true],
                    'created_at' => ['type' => 'string', 'format' => 'date-time'],
                ],
            ],
            'AuditLogListResponse' => [
                'type' => 'object',
                'properties' => [
                    'data' => ['type' => 'array', 'items' => ['$ref' => '#/components/schemas/AuditLog']],
                    'meta' => ['$ref' => '#/components/schemas/ListMeta'],
                ],
            ],
            'ListMeta' => [
                'type' => 'object',
                'properties' => [
                    'total' => ['type' => 'integer'],
                    'page' => ['type' => 'integer'],
                    'per_page' => ['type' => 'integer'],
                ],
            ],
        ];
    }
}
