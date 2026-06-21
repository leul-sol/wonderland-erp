<?php

namespace App\Services;

use App\Models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\File;
use RuntimeException;

class JwtService
{
    public function ensureKeysExist(): void
    {
        $private = config('jwt.private_key_path');
        $public = config('jwt.public_key_path');

        if (File::exists($private) && File::exists($public)) {
            return;
        }

        File::ensureDirectoryExists(dirname($private));

        $resource = \openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        if ($resource === false) {
            throw new RuntimeException('Unable to generate JWT RSA key pair.');
        }

        if (! \openssl_pkey_export($resource, $privateKey)) {
            throw new RuntimeException('Unable to export JWT private key.');
        }
        $details = \openssl_pkey_get_details($resource);
        File::put($private, $privateKey);
        File::put($public, $details['key']);
    }

    public function issueAccessToken(User $user): string
    {
        $this->ensureKeysExist();

        $now = time();
        $payload = array_merge($this->buildClaims($user), [
            'iat' => $now,
            'exp' => $now + (config('jwt.ttl') * 60),
            'iss' => config('jwt.issuer'),
        ]);

        return JWT::encode($payload, $this->privateKey(), config('jwt.algo'), config('jwt.kid'));
    }

    public function decodeAccessToken(string $token): object
    {
        $this->ensureKeysExist();

        return JWT::decode($token, new Key($this->publicKey(), config('jwt.algo')));
    }

    public function buildClaims(User $user): array
    {
        $user->loadMissing('roles');

        return [
            'sub' => (string) $user->id,
            'employee_id' => $user->employee_id !== null ? (string) $user->employee_id : null,
            'username' => $user->username,
            'name' => $user->display_name ?? $user->username,
            'roles' => $user->roleSlugs(),
            'permissions' => $user->permissionStrings(),
            'dept_scope' => $user->departmentScope(),
        ];
    }

    public function jwks(): array
    {
        $this->ensureKeysExist();
        $details = \openssl_pkey_get_details(\openssl_pkey_get_public($this->publicKey()));

        return [
            'keys' => [[
                'kty' => 'RSA',
                'use' => 'sig',
                'alg' => config('jwt.algo'),
                'kid' => config('jwt.kid'),
                'n' => $this->base64UrlEncode($details['rsa']['n']),
                'e' => $this->base64UrlEncode($details['rsa']['e']),
            ]],
        ];
    }

    private function privateKey(): string
    {
        return File::get(config('jwt.private_key_path'));
    }

    private function publicKey(): string
    {
        return File::get(config('jwt.public_key_path'));
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
