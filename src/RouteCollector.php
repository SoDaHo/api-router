<?php

namespace Sodaho\ApiRouter;

/**
 * Class RouteCollector
 *
 * Provides the public API for defining routes and their associated middleware.
 */
class RouteCollector
{
    /** @var array<array{0: string, 1: string, 2: mixed, 3: list<string>}> The collected routes. */
    private array $routes = [];

    /** @var list<list<string>> The stack for nested middleware groups. */
    private array $middlewareStack = [];

    /** @var string[] The stack for nested group prefixes. */
    private array $groupStack = [];

    public function addRoute(string $httpMethod, string $route, mixed $handler): void
    {
        $route = $this->getCurrentPrefix() . $route;
        $middleware = $this->getCurrentMiddleware();
        $this->routes[] = [$httpMethod, $route, $handler, $middleware];
    }

    public function middleware(string|array $middleware, callable $callback): void
    {
        $this->middlewareStack[] = is_array($middleware) ? $middleware : [$middleware];
        $callback($this);
        array_pop($this->middlewareStack);
    }

    public function group(string $prefix, callable $callback): void
    {
        $this->groupStack[] = $prefix;
        $callback($this);
        array_pop($this->groupStack);
    }

    public function get(string $route, mixed $handler): void
    {
        $this->addRoute('GET', $route, $handler);
    }

    public function post(string $route, mixed $handler): void
    {
        $this->addRoute('POST', $route, $handler);
    }

    public function put(string $route, mixed $handler): void
    {
        $this->addRoute('PUT', $route, $handler);
    }

    public function delete(string $route, mixed $handler): void
    {
        $this->addRoute('DELETE', $route, $handler);
    }

    public function getRoutes(): array
    {
        return $this->routes;
    }

    private function getCurrentPrefix(): string
    {
        return implode('', $this->groupStack);
    }

    private function getCurrentMiddleware(): array
    {
        return empty($this->middlewareStack) ? [] : array_merge(...$this->middlewareStack);
    }
}