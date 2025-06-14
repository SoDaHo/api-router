<?php

namespace Sodaho\ApiRouter;

/**
 * Class Dispatcher
 *
 * The core of the router. It takes a request method and URI and finds the
 * matching route handler and parameters.
 */
class Dispatcher
{
    public const NOT_FOUND = 0;
    public const FOUND = 1;
    public const METHOD_NOT_ALLOWED = 2;

    private array $staticRoutes;
    private array $variableRoutes;

    /**
     * @param array $dispatchData The pre-compiled route data from the Router class.
     */
    public function __construct(array $dispatchData)
    {
        $this->staticRoutes = $dispatchData[0];
        $this->variableRoutes = $dispatchData[1];
    }

    /**
     * Dispatches against the provided HTTP method and URI.
     *
     * @param string $httpMethod The request's HTTP method.
     * @param string $uri The request's URI.
     * @return array An array containing the dispatch status, and handler and vars on success.
     */
    public function dispatch(string $httpMethod, string $uri): array
    {
        // First, check for a direct match in static routes (fastest).
        if (isset($this->staticRoutes[$httpMethod][$uri])) {
            return [self::FOUND, $this->staticRoutes[$httpMethod][$uri], []];
        }

        // If no static route was found, check variable routes.
        if (isset($this->variableRoutes[$httpMethod])) {
            foreach ($this->variableRoutes[$httpMethod] as [$regex, $handler, $varNames]) {
                if (preg_match($regex, $uri, $matches)) {
                    array_shift($matches);
                    $vars = count($varNames) > 0 ? array_combine($varNames, $matches) : [];
                    return [self::FOUND, $handler, $vars];
                }
            }
        }

        // If no route is found, check if the URI exists for other HTTP methods.
        $allowedMethods = [];
        $allHttpMethods = array_unique(array_merge(array_keys($this->staticRoutes), array_keys($this->variableRoutes)));

        foreach ($allHttpMethods as $method) {
            if ($method === $httpMethod) continue;

            if (isset($this->staticRoutes[$method][$uri])) {
                $allowedMethods[] = $method;
            } else if (isset($this->variableRoutes[$method])) {
                foreach ($this->variableRoutes[$method] as [$regex, $_, $__]) {
                    if (preg_match($regex, $uri)) {
                        $allowedMethods[] = $method;
                        break; // Found a match for this method, no need to check other regexes for it.
                    }
                }
            }
        }

        if (!empty($allowedMethods)) {
            return [self::METHOD_NOT_ALLOWED, array_unique($allowedMethods)];
        }

        // If nothing was found, return a NOT_FOUND status.
        return [self::NOT_FOUND];
    }
}
