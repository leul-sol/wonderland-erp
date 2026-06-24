<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Client\Response;

class ApiException extends Exception
{
    /**
     * @param  array<string, mixed>  $details
     */
    public function __construct(
        public readonly string $errorCode,
        string $message,
        public readonly int $statusCode = 422,
        public readonly array $details = [],
    ) {
        parent::__construct($message, $statusCode);
    }

    public static function fromResponse(Response $response): self
    {
        $body = $response->json();
        $error = is_array($body) ? ($body['error'] ?? null) : null;

        if (is_array($error)) {
            return new self(
                (string) ($error['code'] ?? 'API_ERROR'),
                (string) ($error['message'] ?? 'Request failed.'),
                $response->status(),
                is_array($error['details'] ?? null) ? $error['details'] : [],
            );
        }

        return new self(
            'API_ERROR',
            trim((string) $response->body()) !== '' ? (string) $response->body() : 'Request failed.',
            $response->status(),
        );
    }
}
