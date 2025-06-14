<?php

namespace Sodaho\ApiRouter;

/**
 * Class RouteCollector
 *
 * Provides the public API for defining routes. It collects all route
 * definitions and supports grouping them with a shared prefix.
 */
class RouteCollector
{
    /** @var array<array{0: string, 1: string, 2: mixed}> The collected routes. */
    private array $routes = [];

    /** @var string[] The stack for nested group prefixes. */
    private array $groupStack = [];

    /**
     * Adds a new route to the collection.
     *
     * @param string $httpMethod The HTTP method (e.g., 'GET', 'POST').
     * @param string $route The route pattern (e.g., '/user/{id}').
     * @param mixed $handler The handler to be executed (e.g., a Closure or ['Controller', 'method']).
     */
    public function addRoute(string $httpMethod, string $route, mixed $handler): void
    {
        $route = $this->getCurrentPrefix() . $route;
        $this->routes[] = [$httpMethod, $route, $handler];
    }

    /**
     * A shortcut for adding a GET route.
     */
    public function get(string $route, mixed $handler): void
    {
        $this->addRoute('GET', $route, $handler);
    }

    /**
     * A shortcut for adding a POST route.
     */
    public function post(string $route, mixed $handler): void
    {
        $this->addRoute('POST', $route, $handler);
    }

    /**
     * A shortcut for adding a PUT route.
     */
    public function put(string $route, mixed $handler): void
    {
        $this->addRoute('PUT', $route, $handler);
    }

    /**
     * A shortcut for adding a DELETE route.
     */
    public function delete(string $route, mixed $handler): void
    {
        $this->addRoute('DELETE', $route, $handler);
    }

    /**
     * Creates a route group with a common prefix.
     *
     * @param string $prefix The prefix for all routes within the group.
     * @param callable $callback The function that receives the RouteCollector to define routes inside the group.
     */
    public function group(string $prefix, callable $callback): void
    {
        $this->groupStack[] = $prefix;
        $callback($this);
        array_pop($this->groupStack);
    }

    /**
     * Returns all collected routes.
     * @return array<array{0: string, 1: string, 2: mixed}>
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    /**
     * Compiles the current prefix from the group stack.
     */
    private function getCurrentPrefix(): string
    {
        return implode('', $this->groupStack);
    }
}
