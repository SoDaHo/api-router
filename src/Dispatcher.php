<?php

namespace Sodaho\ApiRouter;

use Sodaho\ApiRouter\Middleware\MiddlewareInterface;

class Dispatcher
{
    public const NOT_FOUND = 0;
    public const FOUND = 1;
    public const METHOD_NOT_ALLOWED = 2;

    private array $staticRoutes;
    private array $variableRoutes;
    private string $basePath;

    public function __construct(array $dispatchData, string $basePath = '')
    {
        $this->staticRoutes = $dispatchData[0];
        $this->variableRoutes = $dispatchData[1];
        $this->basePath = $basePath;
    }

    public function dispatch(string $httpMethod, string $uri): array
    {
        if ($this->basePath !== '' && str_starts_with($uri, $this->basePath)) {
            $uri = substr($uri, strlen($this->basePath));
            if ($uri === '' || $uri[0] !== '/') {
                $uri = '/' . $uri;
            }
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

        $allowedMethods = $this->findAllowedMethods($uri);
        if (!empty($allowedMethods)) {
            return [self::METHOD_NOT_ALLOWED, $allowedMethods];
        }

        return [self::NOT_FOUND];
    }

    public function runMiddleware(array $middlewareList): void
    {
        foreach ($middlewareList as $middlewareInfo) {
            $middlewareClass = $middlewareInfo['class'];
            $params = $middlewareInfo['params'];

            if (!class_exists($middlewareClass)) {
                throw new \Exception("Middleware class not found: {$middlewareClass}");
            }

            $reflection = new \ReflectionClass($middlewareClass);
            $middleware = $reflection->newInstanceArgs($params);

            if (!$middleware instanceof MiddlewareInterface) {
                throw new \Exception("Middleware class {$middlewareClass} must implement MiddlewareInterface.");
            }

            $middleware->handle();
        }
    }

    private function findAllowedMethods(string $uri): array
    {
        $allowedMethods = [];
        $allHttpMethods = array_unique(array_merge(array_keys($this->staticRoutes), array_keys($this->variableRoutes)));

        foreach ($allHttpMethods as $method) {
            if (isset($this->staticRoutes[$method][$uri])) {
                $allowedMethods[] = $method;
                continue;
            }

            if (isset($this->variableRoutes[$method])) {
                foreach ($this->variableRoutes[$method] as [$regex, $_, $__]) {
                    if (preg_match($regex, $uri)) {
                        $allowedMethods[] = $method;
                        break;
                    }
                }
            }
        }
        return array_unique($allowedMethods);
    }
}