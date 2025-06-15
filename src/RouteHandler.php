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
        $vars = array_values($request->getAttributes());

        $controller = new $class();
        return $controller->{$method}(...$vars);
    }
}