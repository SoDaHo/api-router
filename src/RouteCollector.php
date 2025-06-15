<?php

namespace Sodaho\ApiRouter;

class RouteCollector
{
    /** @var array<array{httpMethod: string, route: string, handler: mixed, middleware: list<array{class: string, params: list<mixed>}>}> */
    private array $routes = [];
    /** @var list<list<array{class: string, params: list<mixed>}>> */
    private array $middlewareGroupStack = [];
    /** @var string[] */
    private array $prefixGroupStack = [];

    public function addRoute(string $httpMethod, string $route, mixed $handler): self
    {
        $prefix = implode('', $this->prefixGroupStack);
        $middleware = empty($this->middlewareGroupStack) ? [] : array_merge(...$this->middlewareGroupStack);

        $this->routes[] = [
            'httpMethod' => $httpMethod,
            'route' => $prefix . $route,
            'handler' => $handler,
            'middleware' => $middleware,
        ];

        return $this;
    }

    public function middleware(string $middleware, ...$params): self
    {
        if (empty($this->routes)) {
            throw new \LogicException('Cannot apply middleware to a route before it is defined.');
        }

        $this->routes[array_key_last($this->routes)]['middleware'][] = [
            'class' => $middleware,
            'params' => $params
        ];

        return $this;
    }

    public function group(string $prefix, callable $callback): void
    {
        $this->prefixGroupStack[] = $prefix;
        $callback($this);
        array_pop($this->prefixGroupStack);
    }

    public function middlewareGroup(array|string $middleware, callable $callback): void
    {
        $middlewares = is_string($middleware) ? [$middleware] : $middleware;

        $middlewareList = [];
        foreach ($middlewares as $mw) {
            if (is_array($mw)) {
                // This handles the new syntax: [PermissionMiddleware::class, 'users.manage']
                $middlewareList[] = ['class' => $mw[0], 'params' => array_slice($mw, 1)];
            } else {
                // This handles the old syntax for single middleware without params
                $middlewareList[] = ['class' => $mw, 'params' => []];
            }
        }

        $this->middlewareGroupStack[] = $middlewareList;
        $callback($this);
        array_pop($this->middlewareGroupStack);
    }

    public function get(string $route, mixed $handler): self
    {
        return $this->addRoute('GET', $route, $handler);
    }

    public function post(string $route, mixed $handler): self
    {
        return $this->addRoute('POST', $route, $handler);
    }

    public function put(string $route, mixed $handler): self
    {
        return $this->addRoute('PUT', $route, $handler);
    }

    public function delete(string $route, mixed $handler): self
    {
        return $this->addRoute('DELETE', $route, $handler);
    }

    public function getRoutes(): array
    {
        return $this->routes;
    }
}