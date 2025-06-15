<?php

namespace Sodaho\ApiRouter\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sodaho\ApiRouter\Controllers\ApiController;
use Sodaho\ApiRouter\Middleware\ExampleAuthMiddleware;
use Sodaho\ApiRouter\Middleware\PermissionMiddleware;
use Sodaho\ApiRouter\RouteCollector;

class RouteCollectorTest extends TestCase
{
    #[Test]
    public function it_adds_a_simple_route(): void
    {
        $collector = new RouteCollector();
        $collector->get('/test', [ApiController::class, 'status']);

        $routes = $collector->getRoutes();

        $this->assertCount(1, $routes);
        $this->assertSame('GET', $routes[0]['httpMethod']);
        $this->assertSame('/test', $routes[0]['route']);
        $this->assertSame([ApiController::class, 'status'], $routes[0]['handler']);
        $this->assertEmpty($routes[0]['middleware']);
    }

    #[Test]
    public function it_adds_a_route_within_a_prefix_group(): void
    {
        $collector = new RouteCollector();

        $collector->group('/api', function (RouteCollector $r) {
            $r->get('/users', [ApiController::class, 'getProducts']);
        });

        $routes = $collector->getRoutes();

        $this->assertCount(1, $routes);
        $this->assertSame('/api/users', $routes[0]['route']);
    }

    #[Test]
    public function it_adds_a_route_within_a_middleware_group(): void
    {
        $collector = new RouteCollector();

        $collector->middlewareGroup(ExampleAuthMiddleware::class, function (RouteCollector $r) {
            $r->get('/protected', [ApiController::class, 'status']);
        });

        $routes = $collector->getRoutes();

        $this->assertCount(1, $routes);
        $this->assertCount(1, $routes[0]['middleware']);
        $this->assertSame(ExampleAuthMiddleware::class, $routes[0]['middleware'][0]['class']);
    }

    #[Test]
    public function it_chains_middleware_to_a_route(): void
    {
        $collector = new RouteCollector();

        $collector->get('/admin', [ApiController::class, 'status'])
            ->middleware(PermissionMiddleware::class, 'admin.access');

        $routes = $collector->getRoutes();

        $this->assertCount(1, $routes);
        $this->assertCount(1, $routes[0]['middleware']);
        $this->assertSame(PermissionMiddleware::class, $routes[0]['middleware'][0]['class']);
        $this->assertSame(['admin.access'], $routes[0]['middleware'][0]['params']);
    }

    #[Test]
    public function it_combines_group_and_chained_middleware(): void
    {
        $collector = new RouteCollector();

        $collector->middlewareGroup(ExampleAuthMiddleware::class, function (RouteCollector $r) {
            $r->get('/protected', [ApiController::class, 'status'])
                ->middleware(PermissionMiddleware::class, 'user.view');
        });

        $routes = $collector->getRoutes();

        $this->assertCount(1, $routes);
        $this->assertCount(2, $routes[0]['middleware']);
        $this->assertSame(ExampleAuthMiddleware::class, $routes[0]['middleware'][0]['class']);
        $this->assertSame(PermissionMiddleware::class, $routes[0]['middleware'][1]['class']);
    }
}