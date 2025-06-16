<?php

namespace Sodaho\ApiRouter\Tests;

use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Sodaho\ApiRouter\Dispatcher;
use Sodaho\ApiRouter\Tests\Fixtures\TestController;
use Sodaho\ApiRouter\Middleware\ExampleAuthMiddleware;

class DispatcherTest extends TestCase
{
    private function createRequest(string $method, string $uri): ServerRequest
    {
        return new ServerRequest($method, $uri);
    }

    #[Test]
    public function it_returns_not_found_response_for_unknown_route(): void
    {
        // Arrange: Dispatcher with empty routes, no container.
        $dispatcher = new Dispatcher([[], []], '', null);
        $request = $this->createRequest('GET', '/not-found');

        // Act
        $response = $dispatcher->handle($request);

        // Assert
        $this->assertSame(404, $response->getStatusCode());
    }

    #[Test]
    public function it_finds_static_route_and_returns_handler_response(): void
    {
        // Arrange: Define a static route, no container needed.
        $staticRoutes = [
            'GET' => [
                '/test' => [
                    'handler' => [TestController::class, 'index'],
                    'middleware' => []
                ]
            ]
        ];
        $dispatcher = new Dispatcher([$staticRoutes, []], '', null);
        $request = $this->createRequest('GET', '/test');

        // Act
        $response = $dispatcher->handle($request);

        // Assert
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Hello from TestController', (string) $response->getBody());
    }

    #[Test]
    public function it_uses_container_to_create_middleware_if_available(): void
    {
        // Arrange
        $middlewareInstance = new ExampleAuthMiddleware();

        // Create a mock container that knows how to resolve our middleware
        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->with(ExampleAuthMiddleware::class)->willReturn(true);
        $container->method('get')->with(ExampleAuthMiddleware::class)->willReturn($middlewareInstance);

        // Define a route that uses this middleware
        $staticRoutes = [
            'GET' => [
                '/protected' => [
                    'handler' => [TestController::class, 'index'],
                    'middleware' => [
                        // Note: Params are ignored if middleware is in container
                        ['class' => ExampleAuthMiddleware::class, 'params' => []]
                    ]
                ]
            ]
        ];
        $dispatcher = new Dispatcher([$staticRoutes, []], '', $container);

        // Act: Make a request that will fail auth, proving the middleware was executed
        $request = $this->createRequest('GET', '/protected');
        $response = $dispatcher->handle($request);

        // Assert: 401 proves the middleware from the container was executed
        $this->assertSame(401, $response->getStatusCode());
    }
}
