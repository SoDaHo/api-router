<?php

namespace Sodaho\ApiRouter\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Sodaho\ApiRouter\Http\JsonResponse;

class ExampleAuthMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!$request->hasHeader('Authorization')) {
            return new JsonResponse(['error' => 'Unauthorized'], 401);
        }

        // In a real app, you would validate the token here.

        return $handler->handle($request);
    }
}