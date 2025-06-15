<?php

namespace Sodaho\ApiRouter;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class Router implements RequestHandlerInterface
{
    public const NOT_FOUND = 0;
    public const FOUND = 1;
    public const METHOD_NOT_ALLOWED = 2;

    private Dispatcher $dispatcher;

    public function __construct(callable $routeDefinitionCallback, array $options = [])
    {
        $options = array_merge([
            'cacheFile' => null,
            'cacheDisabled' => false,
            'basePath' => '',
        ], $options);

        if (!$options['cacheDisabled'] && $options['cacheFile'] && file_exists($options['cacheFile'])) {
            $dispatchData = require $options['cacheFile'];
        } else {
            $routeCollector = new RouteCollector();
            $routeDefinitionCallback($routeCollector);
            $dispatchData = $this->prepareDispatchData($routeCollector->getRoutes());

            if (!$options['cacheDisabled'] && $options['cacheFile']) {
                $cacheDir = dirname($options['cacheFile']);
                if (!is_dir($cacheDir)) {
                    // This can be improved with a dedicated exception
                    mkdir($cacheDir, 0777, true);
                }
                file_put_contents($options['cacheFile'], '<?php return ' . var_export($dispatchData, true) . ';');
            }
        }

        $this->dispatcher = new Dispatcher($dispatchData, $options['basePath']);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->dispatcher->handle($request);
    }

    private function prepareDispatchData(array $routes): array
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