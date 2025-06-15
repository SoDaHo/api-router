<?php

namespace Sodaho\ApiRouter\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sodaho\ApiRouter\Controllers\ApiController;
use Sodaho\ApiRouter\Dispatcher;
use Sodaho\ApiRouter\Middleware\PermissionMiddleware;

class DispatcherTest extends TestCase
{
    private function createDispatcher(array $routes): Dispatcher
    {
        // We manually create the dispatch data that the Router class would normally generate.
        $staticRoutes = [];
        $variableRoutes = [];

        foreach ($routes as $route) {
            if (!str_contains($route['path'], '{')) {
                $staticRoutes[$route['method']][$route['path']] = [
                    'handler' => $route['handler'],
                    'middleware' => $route['middleware'] ?? []
                ];
            } else {
                // Simplified regex for testing purposes
                $pattern = str_replace('{id:\d+}', '(\d+)', $route['path']);
                $variableRoutes[$route['method']][] = [
                    "~^{$pattern}$~",
                    [
                        'handler' => $route['handler'],
                        'middleware' => $route['middleware'] ?? []
                    ],
                    ['id']
                ];
            }
        }

        return new Dispatcher([$staticRoutes, $variableRoutes]);
    }

    #[Test]
    public function it_finds_a_static_route(): void
    {
        $dispatcher = $this->createDispatcher([
            ['method' => 'GET', 'path' => '/test', 'handler' => 'handler_1']
        ]);

        $result = $dispatcher->dispatch('GET', '/test');

        $this->assertSame(Dispatcher::FOUND, $result[0]);
        $this->assertSame('handler_1', $result[1]);
    }

    #[Test]
    public function it_returns_not_found_for_an_unknown_route(): void
    {
        $dispatcher = $this->createDispatcher([]);

        $result = $dispatcher->dispatch('GET', '/not-found');

        $this->assertSame(Dispatcher::NOT_FOUND, $result[0]);
    }

    #[Test]
    public function it_returns_method_not_allowed_if_method_is_wrong(): void
    {
        $dispatcher = $this->createDispatcher([
            ['method' => 'POST', 'path' => '/test', 'handler' => 'handler_1']
        ]);

        $result = $dispatcher->dispatch('GET', '/test');

        $this->assertSame(Dispatcher::METHOD_NOT_ALLOWED, $result[0]);
        $this->assertSame(['POST'], $result[1]);
    }

    #[Test]
    public function it_finds_a_dynamic_route_and_extracts_vars(): void
    {
        $dispatcher = $this->createDispatcher([
            ['method' => 'GET', 'path' => '/user/{id:\d+}', 'handler' => 'user_handler']
        ]);

        $result = $dispatcher->dispatch('GET', '/user/123');

        $this->assertSame(Dispatcher::FOUND, $result[0]);
        $this->assertSame('user_handler', $result[1]);
        $this->assertSame(['id' => '123'], $result[2]);
    }

    #[Test]
    public function it_returns_middleware_for_a_found_route(): void
    {
        $middleware = [['class' => PermissionMiddleware::class, 'params' => ['user.view']]];
        $dispatcher = $this->createDispatcher([
            ['method' => 'GET', 'path' => '/secure', 'handler' => 'secure_handler', 'middleware' => $middleware]
        ]);

        $result = $dispatcher->dispatch('GET', '/secure');

        $this->assertSame(Dispatcher::FOUND, $result[0]);
        $this->assertSame($middleware, $result[3]);
    }
}