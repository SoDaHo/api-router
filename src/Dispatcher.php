<?php

namespace Sodaho\ApiRouter;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Sodaho\ApiRouter\Http\JsonResponse;

class Dispatcher implements RequestHandlerInterface
{
    private array $staticRoutes;
    private array $variableRoutes;
    private string $basePath;

    public function __construct(array $dispatchData, string $basePath = '')
    {
        // FIX: Ensure that the keys exist and default to an empty array.
        $this->staticRoutes = $dispatchData[0] ?? [];
        $this->variableRoutes = $dispatchData[1] ?? [];
        $this->basePath = $basePath;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routeInfo = $this->dispatch($request);

        switch ($routeInfo[0]) {
            case Router::NOT_FOUND:
                return new JsonResponse(['error' => 'Not Found'], 404);

            case Router::METHOD_NOT_ALLOWED:
                return new JsonResponse(['error' => 'Method Not Allowed'], 405, ['Allow' => implode(', ', $routeInfo[1])]);

            case Router::FOUND:
                [, $handler, $vars, $middleware] = $routeInfo;

                $request = $this->addVarsToRequest($request, $vars);

                $requestHandler = new RouteHandler($handler);

                foreach (array_reverse($middleware) as $middlewareInfo) {
                    $mwInstance = $this->createMiddleware($middlewareInfo);
                    $requestHandler = new MiddlewareHandler($mwInstance, $requestHandler);
                }

                return $requestHandler->handle($request);
        }

        return new JsonResponse(['error' => 'An unexpected error occurred'], 500);
    }

    private function dispatch(ServerRequestInterface $request): array
    {
        $httpMethod = $request->getMethod();
        $uri = $request->getUri()->getPath();

        if ($this->basePath !== '' && str_starts_with($uri, $this->basePath)) {
            $uri = substr($uri, strlen($this->basePath)) ?: '/';
        }

        if (isset($this->staticRoutes[$httpMethod][$uri])) {
            $routeData = $this->staticRoutes[$httpMethod][$uri];
            return [Router::FOUND, $routeData['handler'], [], $routeData['middleware']];
        }

        if (isset($this->variableRoutes[$httpMethod])) {
            foreach ($this->variableRoutes[$httpMethod] as [$regex, $routeData, $varNames]) {
                if (preg_match($regex, $uri, $matches)) {
                    array_shift($matches);
                    $vars = count($varNames) > 0 ? array_combine($varNames, $matches) : [];
                    return [Router::FOUND, $routeData['handler'], $vars, $routeData['middleware']];
                }
            }
        }

        $allowedMethods = $this->findAllowedMethodsForUri($uri);

        if (!empty($allowedMethods)) {
            return [Router::METHOD_NOT_ALLOWED, $allowedMethods];
        }

        return [Router::NOT_FOUND];
    }

    private function findAllowedMethodsForUri(string $uri): array
    {
        $allowedMethods = [];
        $allHttpMethods = array_unique(array_merge(array_keys($this->staticRoutes), array_keys($this->variableRoutes)));

        foreach ($allHttpMethods as $method) {
            if (isset($this->staticRoutes[$method][$uri])) {
                $allowedMethods[] = $method;
                continue;
            }
            if (isset($this->variableRoutes[$method])) {
                foreach ($this->variableRoutes[$method] as [$regex, $routeData, $varNames]) {
                    if (preg_match($regex, $uri)) {
                        $allowedMethods[] = $method;
                        break;
                    }
                }
            }
        }
        return array_unique($allowedMethods);
    }

    private function addVarsToRequest(ServerRequestInterface $request, array $vars): ServerRequestInterface
    {
        foreach ($vars as $key => $value) {
            $request = $request->withAttribute($key, $value);
        }
        return $request;
    }

    private function createMiddleware(array $middlewareInfo): Middleware\MiddlewareInterface
    {
        $middlewareClass = $middlewareInfo['class'];
        $params = $middlewareInfo['params'];

        $reflection = new \ReflectionClass($middlewareClass);
        return $reflection->newInstanceArgs($params);
    }
}