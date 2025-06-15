<?php

namespace Sodaho\ApiRouter\Tests;

use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sodaho\ApiRouter\Dispatcher;
use Sodaho\ApiRouter\Router;
use Sodaho\ApiRouter\Tests\Fixtures\TestController;

class DispatcherTest extends TestCase
{
    private function createRequest(string $method, string $uri): ServerRequest
    {
        return new ServerRequest($method, $uri);
    }

    #[Test]
    public function it_returns_not_found_response_for_unknown_route(): void
    {
        // FIX: Pass a correctly structured (but empty) dispatch data array.
        $dispatcher = new Dispatcher([[], []], '');
        $request = $this->createRequest('GET', '/not-found');

        $response = $dispatcher->handle($request);

        $this->assertSame(404, $response->getStatusCode());
    }

    #[Test]
    public function it_finds_static_route_and_returns_handler_response(): void
    {
        $staticRoutes = [
            'GET' => [
                '/test' => [
                    'handler' => [TestController::class, 'index'],
                    'middleware' => []
                ]
            ]
        ];
        $dispatcher = new Dispatcher([$staticRoutes, []], '');
        $request = $this->createRequest('GET', '/test');

        $response = $dispatcher->handle($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Hello from TestController', (string) $response->getBody());
    }
}