<?php
// tests/Fixtures/TestControllerWithDependency.php
namespace Sodaho\ApiRouter\Tests\Fixtures;

use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;

/**
 * A controller that requires a dependency via its constructor.
 */
class TestControllerWithDependency
{
    private TestService $service;

    public function __construct(TestService $service)
    {
        $this->service = $service;
    }

    public function index(): ResponseInterface
    {
        $message = $this->service->getMessage();
        return new Response(200, [], $message);
    }
}
