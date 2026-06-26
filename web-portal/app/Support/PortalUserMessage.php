<?php

namespace App\Support;

use App\Exceptions\ApiException;

class PortalUserMessage
{
    /**
     * @return array{title: string, message: string, recommendation: string, code: string}
     */
    public static function fromApiException(ApiException $exception): array
    {
        $code = $exception->errorCode;
        $raw = trim($exception->getMessage());
        $status = $exception->statusCode;

        if (self::isConnectionIssue($code, $raw)) {
            return self::connectionIssue($code);
        }

        return match ($code) {
            'UNAUTHENTICATED' => [
                'title' => 'Session expired',
                'message' => 'Your login session is no longer valid.',
                'recommendation' => 'Sign in again to continue.',
                'code' => $code,
            ],
            'FORBIDDEN' => [
                'title' => 'Access denied',
                'message' => self::sanitize($raw) ?: 'You do not have permission to perform this action.',
                'recommendation' => 'Ask your manager or system administrator if you need access.',
                'code' => $code,
            ],
            'VALIDATION_ERROR', 'INVALID_REQUEST' => self::validation($raw, $exception->details),
            'NOT_FOUND' => [
                'title' => 'Not found',
                'message' => self::sanitize($raw) ?: 'The record you requested could not be found.',
                'recommendation' => 'It may have been removed. Go back and refresh the list.',
                'code' => $code,
            ],
            default => self::generic($code, $raw, $status),
        };
    }

    /**
     * @return array{title: string, message: string, recommendation: string, code: string}
     */
    public static function fromRaw(string $message, string $code = 'ERROR'): array
    {
        if (self::looksTechnical($message)) {
            return self::connectionIssue($code);
        }

        return [
            'title' => 'Something went wrong',
            'message' => $message !== '' ? $message : 'An unexpected error occurred.',
            'recommendation' => 'Try again. If the problem continues, contact your IT support.',
            'code' => $code,
        ];
    }

    public static function sanitize(string $message): string
    {
        $message = trim($message);

        if ($message === '' || self::looksTechnical($message)) {
            return '';
        }

        return $message;
    }

    /**
     * @param  array<string, mixed>  $details
     * @return array{title: string, message: string, recommendation: string, code: string}
     */
    private static function validation(string $raw, array $details): array
    {
        $message = self::sanitize($raw) ?: 'Some fields need your attention.';

        if ($details !== []) {
            $fields = implode(', ', array_keys($details));
            if ($fields !== '') {
                $message = "Please check: {$fields}.";
            }
        }

        return [
            'title' => 'Please fix the form',
            'message' => $message,
            'recommendation' => 'Correct the highlighted fields and try again.',
            'code' => 'VALIDATION_ERROR',
        ];
    }

    /**
     * @return array{title: string, message: string, recommendation: string, code: string}
     */
    private static function connectionIssue(string $code): array
    {
        return [
            'title' => 'Connection problem',
            'message' => 'We could not reach the server. This often happens when the internet is slow or a background service is still starting.',
            'recommendation' => 'Check your connection, wait a few seconds, and try again. Contact IT support if this keeps happening.',
            'code' => $code === 'API_ERROR' ? 'SERVICE_UNAVAILABLE' : $code,
        ];
    }

    /**
     * @return array{title: string, message: string, recommendation: string, code: string}
     */
    private static function generic(string $code, string $raw, int $status): array
    {
        $sanitized = self::sanitize($raw);

        if ($status >= 500) {
            return [
                'title' => 'Temporary system issue',
                'message' => $sanitized ?: 'The system could not complete your request right now.',
                'recommendation' => 'Wait a moment and try again. Contact IT support if the problem continues.',
                'code' => $code,
            ];
        }

        return [
            'title' => 'Request could not be completed',
            'message' => $sanitized ?: 'Something went wrong while processing your request.',
            'recommendation' => 'Try again. If the problem continues, contact your IT support.',
            'code' => $code,
        ];
    }

    private static function isConnectionIssue(string $code, string $raw): bool
    {
        if (in_array($code, ['SERVICE_UNAVAILABLE', 'GATEWAY_TIMEOUT', 'CONNECTION_ERROR'], true)) {
            return true;
        }

        return self::looksTechnical($raw);
    }

    private static function looksTechnical(string $text): bool
    {
        if ($text === '') {
            return false;
        }

        $needles = [
            'cURL error',
            'Connection refused',
            'Connection timed out',
            'Operation timed out',
            'stacktrace',
            '/var/www/',
            's1-identity',
            's2-workforce',
            's3-hospitality',
            'wh-gateway',
            'Illuminate\\',
            'GuzzleHttp\\',
            '"trace"',
            'docker compose',
        ];

        foreach ($needles as $needle) {
            if (stripos($text, $needle) !== false) {
                return true;
            }
        }

        return strlen($text) > 320;
    }
}
