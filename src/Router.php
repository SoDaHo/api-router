<?php

namespace Sodaho\ApiRouter;

use Sodaho\ApiRouter\Exception\CacheDirectoryException;

class Router
{
    public static function createDispatcher(callable $routeDefinitionCallback, array $options = []): Dispatcher
    {
        $options = array_merge([
            'cacheFile' => null,
            'cacheDisabled' => false,
            'basePath' => '',
        ], $options);

        if (!$options['cacheDisabled'] && $options['cacheFile'] && file_exists($options['cacheFile'])) {
            $dispatchData = require $options['cacheFile'];
            return new Dispatcher($dispatchData, $options['basePath']);
        }

        $routeCollector = new RouteCollector();
        $routeDefinitionCallback($routeCollector);

        $dispatchData = self::prepareDispatchData($routeCollector->getRoutes());

        if (!$options['cacheDisabled'] && $options['cacheFile']) {
            $cacheDir = dirname($options['cacheFile']);
            if (!is_dir($cacheDir)) {
                if (false === @mkdir($cacheDir, 0777, true) && !is_dir($cacheDir)) {
                    throw new CacheDirectoryException(sprintf('Cache directory "%s" could not be created.', $cacheDir));
                }
            }
            $cacheContents = '<?php return ' . var_export($dispatchData, true) . ';';
            file_put_contents($options['cacheFile'], $cacheContents);
        }

        return new Dispatcher($dispatchData, $options['basePath']);
    }

    private static function prepareDispatchData(array $routes): array
    {
        $staticRoutes = [];
        $variableRoutes = [];
        $routeParser = new RouteParser();

        foreach ($routes as $routeData) {
            $route = $routeData['route'];
            $httpMethod = $routeData['httpMethod'];

            $dataToStore = [
                'handler' => $routeData['handler'],
                'middleware' => $routeData['middleware']
            ];

            if (!str_contains($route, '{')) {
                $staticRoutes[$httpMethod][$route] = $dataToStore;
            } else {
                [$regex, $variableNames] = $routeParser->parse($route);
                $fullRegex = '~^' . $regex . '$~';
                $variableRoutes[$httpMethod][] = [$fullRegex, $dataToStore, $variableNames];
            }
        }

        return [$staticRoutes, $variableRoutes];
    }
}