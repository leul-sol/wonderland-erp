<?php

namespace App\Exceptions;

use App\Support\PortalUserMessage;
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

    /**
     * @return array{title: string, message: string, recommendation: string, code: string}
     */
    public function userMessage(): array
    {
        return PortalUserMessage::fromApiException($this);
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

        if (is_array($body) && isset($body['message'])) {
            $message = (string) $body['message'];
            $code = match (true) {
                $response->status() === 401 => 'UNAUTHENTICATED',
                $response->status() === 403 => 'FORBIDDEN',
                $response->status() === 404 => 'NOT_FOUND',
                $response->status() === 422 => 'VALIDATION_ERROR',
                $response->status() >= 500 => 'SERVER_ERROR',
                default => 'API_ERROR',
            };

            return new self($code, $message, $response->status());
        }

        $rawBody = trim((string) $response->body());

        return new self(
            $response->status() >= 500 ? 'SERVER_ERROR' : 'API_ERROR',
            $rawBody !== '' ? $rawBody : 'Request failed.',
            $response->status(),
        );
    }
}
