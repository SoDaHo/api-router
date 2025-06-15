<?php

namespace Sodaho\ApiRouter;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RouteHandler implements RequestHandlerInterface
{
    public function __construct(private mixed $handler)
    {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        [$class, $method] = $this->handler;

        // Route-Parameter aus den Request-Attributen holen
        $routeAttributes = $request->getAttributes();

        $controller = new $class();

        // WICHTIG: Das Request-Objekt und die Route-Parameter werden übergeben
        return $controller->{$method}($request, ...array_values($routeAttributes));
    }
}