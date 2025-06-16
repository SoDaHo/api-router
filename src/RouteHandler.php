<?php

namespace Sodaho\ApiRouter;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RouteHandler implements RequestHandlerInterface
{
    /**
     * @param mixed $handler The route handler, typically an array of [class, method].
     * @param ContainerInterface|null $container An optional PSR-11 container.
     */
    public function __construct(
        private mixed $handler,
        private ?ContainerInterface $container = null
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        [$class, $method] = $this->handler;

        // Get route parameters from request attributes
        $routeAttributes = $request->getAttributes();

        // Instantiate the controller
        // Use the container if it exists, otherwise fall back to direct instantiation.
        $controller = ($this->container && $this->container->has($class))
            ? $this->container->get($class)
            : new $class();

        // Pass the request object and the route parameters to the controller method.
        return $controller->{$method}($request, ...array_values($routeAttributes));
    }
}
