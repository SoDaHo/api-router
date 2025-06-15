<?php

namespace Sodaho\ApiRouter\Tests\Integration;

use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sodaho\ApiRouter\Controllers\ApiController;
use Sodaho\ApiRouter\Middleware\ExampleAuthMiddleware;
use Sodaho\ApiRouter\Middleware\PermissionMiddleware;
use Sodaho\ApiRouter\Router;

class RoutingTest extends TestCase
{
    /**
     * Helper function to create a new router instance for each test,
     * ensuring that tests are isolated from each other.
     */
    private function createApp(callable $routeDefinition): Router
    {
        return new Router($routeDefinition, ['cacheDisabled' => true]);
    }

    #[Test]
    public function it_successfully_handles_a_public_route(): void
    {
        // Arrange: Define a router with one public route.
        $app = $this->createApp(function ($r) {
            $r->get('/api/status', [ApiController::class, 'status']);
        });
        $request = new ServerRequest('GET', '/api/status');

        // Act: Handle the request through the entire application stack.
        $response = $app->handle($request);
        $body = json_decode((string) $response->getBody(), true);

        // Assert: Check the final HTTP response.
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('ok', $body['status']);
    }

    #[Test]
    public function it_returns_a_404_response_for_an_unknown_route(): void
    {
        // Arrange: Define a router with no routes.
        $app = $this->createApp(fn($r) => null);
        $request = new ServerRequest('GET', '/non-existent');

        // Act
        $response = $app->handle($request);
        $body = json_decode((string) $response->getBody(), true);

        // Assert
        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame('Not Found', $body['error']);
    }

    #[Test]
    public function it_is_blocked_by_auth_middleware_without_header(): void
    {
        // Arrange: Define a route protected by AuthMiddleware.
        $app = $this->createApp(function ($r) {
            $r->middlewareGroup(ExampleAuthMiddleware::class, function ($r) {
                $r->get('/protected', [ApiController::class, 'getProducts']);
            });
        });
        // This request is missing the 'Authorization' header.
        $request = new ServerRequest('GET', '/protected');

        // Act
        $response = $app->handle($request);
        $body = json_decode((string) $response->getBody(), true);

        // Assert: We expect the middleware to return a 401 Unauthorized response.
        $this->assertSame(401, $response->getStatusCode());
        $this->assertSame('Unauthorized', $body['error']);
    }

    #[Test]
    public function it_is_blocked_by_permission_middleware_without_permission(): void
    {
        // Arrange: Define a route with a specific permission required.
        $app = $this->createApp(function ($r) {
            $r->middlewareGroup(ExampleAuthMiddleware::class, function ($r) {
                $r->get('/admin/products', [ApiController::class, 'getProducts'])
                    ->middleware(PermissionMiddleware::class, 'product.list');
            });
        });

        // This request is authenticated, but MISSING the required permission in the header.
        $request = new ServerRequest('GET', '/admin/products', [
            'Authorization' => 'Bearer some-token',
            'X-User-Permissions' => 'other.perm,another.perm'
        ]);

        // Act
        $response = $app->handle($request);
        $body = json_decode((string) $response->getBody(), true);

        // Assert: We expect a 403 Forbidden response.
        $this->assertSame(403, $response->getStatusCode());
        $this->assertStringContainsString('product.list', $body['error']);
    }

    #[Test]
    public function it_successfully_handles_a_fully_protected_and_permissioned_route(): void
    {
        // Arrange: Define a route with both auth and permission middleware.
        $app = $this->createApp(function ($r) {
            $r->middlewareGroup(ExampleAuthMiddleware::class, function ($r) {
                $r->get('/admin/products', [ApiController::class, 'getProducts'])
                    ->middleware(PermissionMiddleware::class, 'product.list');
            });
        });

        // This request has all the required headers to pass both middlewares.
        $request = new ServerRequest('GET', '/admin/products', [
            'Authorization' => 'Bearer some-token',
            'X-User-Permissions' => 'product.list,other.perm'
        ]);

        // Act
        $response = $app->handle($request);

        // Assert: We expect a 200 OK response because the request passes all checks.
        $this->assertSame(200, $response->getStatusCode());
    }
}