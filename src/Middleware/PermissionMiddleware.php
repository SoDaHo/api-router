<?php

namespace Sodaho\ApiRouter\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Sodaho\ApiRouter\Http\JsonResponse;

class PermissionMiddleware implements MiddlewareInterface
{
    protected string $permission;

    public function __construct(string $permission)
    {
        $this->permission = $permission;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $userPermissionHeader = $request->getHeaderLine('X-User-Permissions');
        $userPermissions = explode(',', $userPermissionHeader);

        if (!in_array($this->permission, $userPermissions, true)) {
            return new JsonResponse(['error' => "Forbidden: Missing required permission '{$this->permission}'"], 403);
        }

        return $handler->handle($request);
    }
}