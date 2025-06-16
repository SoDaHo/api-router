<?php

namespace Sodaho\ApiRouter\Tests\Integration;

use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Sodaho\ApiRouter\Controllers\ApiController;
use Sodaho\ApiRouter\Middleware\ExampleAuthMiddleware;
use Sodaho\ApiRouter\Middleware\PermissionMiddleware;
use Sodaho\ApiRouter\Router;
use Sodaho\ApiRouter\Tests\Fixtures\TestControllerWithDependency;
use Sodaho\ApiRouter\Tests\Fixtures\TestService;

// A simple container implementation for testing purposes, mirroring the one in index.php
class TestContainer implements ContainerInterface {
    private array $entries = [];
    public function get(string $id) {
        if (!isset($this->entries[$id])) { throw new \Exception("Service not found: $id"); }
        $entry = $this->entries[$id];
        return $entry($this);
    }
    public function has(string $id): bool { return isset($this->entries[$id]); }
    public function set(string $id, callable|object $callable): void {
        if(is_callable($callable)) {
            $this->entries[$id] = $callable;
        } else {
            $this->entries[$id] = fn() => $callable;
        }
    }
}

class RoutingTest extends TestCase
{
    /**
     * Helper to create a router instance, now with container support.
     */
    private function createApp(callable $routeDefinition, ?ContainerInterface $container = null): Router
    {
        return new Router($routeDefinition, ['cacheDisabled' => true], $container);
    }

    #[Test]
    public function it_successfully_handles_a_public_route(): void
    {
        // Arrange
        $app = $this->createApp(function ($r) {
            $r->get('/api/status', [ApiController::class, 'status']);
        });
        $request = new ServerRequest('GET', '/api/status');

        // Act
        $response = $app->handle($request);
        $body = json_decode((string) $response->getBody(), true);

        // Assert
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('ok', $body['status']);
    }

    #[Test]
    public function it_instantiates_controller_with_dependencies_from_container(): void
    {
        // Arrange: Setup a container with a service and a controller that needs it.
        $container = new TestContainer();
        $container->set(TestService::class, fn() => new TestService());
        $container->set(TestControllerWithDependency::class,
            fn(ContainerInterface $c) => new TestControllerWithDependency(
                $c->get(TestService::class)
            )
        );

        // Create the app, passing the configured container
        $app = $this->createApp(function ($r) {
            $r->get('/di-test', [TestControllerWithDependency::class, 'index']);
        }, $container);

        $request = new ServerRequest('GET', '/di-test');

        // Act
        $response = $app->handle($request);
        $body = (string) $response->getBody();

        // Assert: The response body proves the service was correctly injected and called.
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Hello from TestService!', $body);
    }

    // --- All other existing tests from RoutingTest remain unchanged ---

    #[Test]
    public function it_returns_a_404_response_for_an_unknown_route(): void
    {
        $app = $this->createApp(fn($r) => null);
        $request = new ServerRequest('GET', '/non-existent');
        $response = $app->handle($request);
        $body = json_decode((string) $response->getBody(), true);
        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame('Not Found', $body['error']);
    }

    #[Test]
    public function it_is_blocked_by_auth_middleware_without_header(): void
    {
        $app = $this->createApp(function ($r) {
            $r->middlewareGroup(ExampleAuthMiddleware::class, function ($r) {
                $r->get('/protected', [ApiController::class, 'getProducts']);
            });
        });
        $request = new ServerRequest('GET', '/protected');
        $response = $app->handle($request);
        $body = json_decode((string) $response->getBody(), true);
        $this->assertSame(401, $response->getStatusCode());
        $this->assertSame('Unauthorized', $body['error']);
    }
}
