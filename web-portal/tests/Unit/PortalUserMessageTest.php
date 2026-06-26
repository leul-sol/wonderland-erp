<?php

namespace Tests\Unit;

use App\Exceptions\ApiException;
use App\Support\PortalUserMessage;
use PHPUnit\Framework\TestCase;

class PortalUserMessageTest extends TestCase
{
    public function test_connection_timeout_is_user_friendly(): void
    {
        $exception = new ApiException(
            'API_ERROR',
            'cURL error 28: Operation timed out after 5001 milliseconds with 0 bytes received for http://s1-identity:9001/api/v1/auth/verify',
            500,
        );

        $message = PortalUserMessage::fromApiException($exception);

        $this->assertSame('Connection problem', $message['title']);
        $this->assertStringNotContainsString('cURL', $message['message']);
        $this->assertStringNotContainsString('s1-identity', $message['message']);
        $this->assertNotEmpty($message['recommendation']);
    }

    public function test_validation_errors_keep_plain_message(): void
    {
        $exception = new ApiException('VALIDATION_ERROR', 'Check-out must be after check-in.', 422);

        $message = PortalUserMessage::fromApiException($exception);

        $this->assertSame('Please fix the form', $message['title']);
        $this->assertStringContainsString('check-in', strtolower($message['message']));
    }
}
