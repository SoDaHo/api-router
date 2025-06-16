<?php

namespace Sodaho\ApiRouter;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Sodaho\ApiRouter\Http\JsonResponse;

class Dispatcher implements RequestHandlerInterface
{
    // Define the dispatch status constants, as they are specific to the dispatcher's logic.
    public const NOT_FOUND = 0;
    public const FOUND = 1;
    public const METHOD_NOT_ALLOWED = 2;

    private array $staticRoutes;
    private array $variableRoutes;
    private string $basePath;
    private ?ContainerInterface $container;

    public function __construct(array $dispatchData, string $basePath = '', ?ContainerInterface $container = null)
    {
        $this->staticRoutes = $dispatchData[0] ?? [];
        $this->variableRoutes = $dispatchData[1] ?? [];
        $this->basePath = $basePath;
        $this->container = $container;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routeInfo = $this->dispatch($request);

        switch ($routeInfo[0]) {
            case self::NOT_FOUND:
                return new JsonResponse(['error' => 'Not Found'], 404);

            case self::METHOD_NOT_ALLOWED:
                return new JsonResponse(['error' => 'Method Not Allowed'], 405, ['Allow' => implode(', ', $routeInfo[1])]);

            case self::FOUND:
                [, $handler, $vars, $middleware] = $routeInfo;

                $request = $this->addVarsToRequest($request, $vars);

                $requestHandler = new RouteHandler($handler, $this->container);

                foreach (array_reverse($middleware) as $middlewareInfo) {
                    $mwInstance = $this->createMiddleware($middlewareInfo);
                    $requestHandler = new MiddlewareHandler($mwInstance, $requestHandler);
                }

                return $requestHandler->handle($request);
        }

        // This should theoretically be unreachable
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
            return [self::FOUND, $routeData['handler'], [], $routeData['middleware']];
        }

        if (isset($this->variableRoutes[$httpMethod])) {
            foreach ($this->variableRoutes[$httpMethod] as [$regex, $routeData, $varNames]) {
                if (preg_match($regex, $uri, $matches)) {
                    array_shift($matches);
                    $vars = count($varNames) > 0 ? array_combine($varNames, $matches) : [];
                    return [self::FOUND, $routeData['handler'], $vars, $routeData['middleware']];
                }
            }
        }

        $allowedMethods = $this->findAllowedMethodsForUri($uri);

        if (!empty($allowedMethods)) {
            return [self::METHOD_NOT_ALLOWED, $allowedMethods];
        }

        return [self::NOT_FOUND];
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
                foreach ($this->variableRoutes[$method] as [$regex, /*$routeData*/, /*$varNames*/]) {
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

        if ($this->container && $this->container->has($middlewareClass)) {
            return $this->container->get($middlewareClass);
        }

        $reflection = new \ReflectionClass($middlewareClass);
        return $reflection->newInstanceArgs($params);
    }
}
