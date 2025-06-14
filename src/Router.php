<?php

namespace Sodaho\ApiRouter;

/**
 * Class Router
 *
 * Main facade for the router. This class handles the high-level logic,
 * including the creation of the dispatcher and route caching.
 */
class Router
{
    /**
     * Creates a Dispatcher instance.
     *
     * This method orchestrates the process of collecting route definitions and preparing
     * them for dispatching. It uses caching if enabled to improve performance on
     * subsequent requests.
     *
     * @param callable $routeDefinitionCallback The function that defines the routes. It receives a RouteCollector instance.
     * @param array $options Configuration options for caching. e.g., ['cacheFile' => 'path/to/cache.php', 'cacheDisabled' => false]
     * @return Dispatcher
     */
    public static function createDispatcher(callable $routeDefinitionCallback, array $options = []): Dispatcher
    {
        $options = array_merge([
            'cacheFile' => null,
            'cacheDisabled' => false,
        ], $options);

        // If caching is enabled and a valid cache file exists, load it directly.
        if (!$options['cacheDisabled'] && $options['cacheFile'] && file_exists($options['cacheFile'])) {
            $dispatchData = require $options['cacheFile'];
            return new Dispatcher($dispatchData);
        }

        // If cache is disabled or not found, generate the dispatch data.
        $routeCollector = new RouteCollector();
        $routeDefinitionCallback($routeCollector);

        $dispatchData = self::prepareDispatchData($routeCollector->getRoutes());

        // If caching is enabled, write the newly generated data to the cache file.
        if (!$options['cacheDisabled'] && $options['cacheFile']) {
            $cacheDir = dirname($options['cacheFile']);
            if (!is_dir($cacheDir)) {
                mkdir($cacheDir, 0755, true);
            }
            $cacheContents = '<?php return ' . var_export($dispatchData, true) . ';';
            file_put_contents($options['cacheFile'], $cacheContents);
        }

        return new Dispatcher($dispatchData);
    }

    /**
     * Prepares the raw route data for the dispatcher and for caching.
     *
     * This method separates routes into static and dynamic (variable) routes for
     * optimized dispatching.
     *
     * @param array $routes The raw routes from RouteCollector.
     * @return array The prepared data, structured for the Dispatcher.
     */
    private static function prepareDispatchData(array $routes): array
    {
        $staticRoutes = [];
        $variableRoutes = [];
        $routeParser = new RouteParser();

        foreach ($routes as [$httpMethod, $route, $handler]) {
            if (!str_contains($route, '{')) {
                // Static routes can be matched quickly with a direct lookup.
                $staticRoutes[$httpMethod][$route] = $handler;
            } else {
                // Variable routes require regex matching.
                [$regex, $variableNames] = $routeParser->parse($route);
                $fullRegex = '~^' . $regex . '$~';
                $variableRoutes[$httpMethod][] = [$fullRegex, $handler, $variableNames];
            }
        }

        return [$staticRoutes, $variableRoutes];
    }
}
