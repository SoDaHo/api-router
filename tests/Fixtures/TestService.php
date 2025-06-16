<?php
// tests/Fixtures/TestService.php
namespace Sodaho\ApiRouter\Tests\Fixtures;

/**
 * A simple dummy service for DI testing purposes.
 */
class TestService
{
    public function getMessage(): string
    {
        return 'Hello from TestService!';
    }
}
