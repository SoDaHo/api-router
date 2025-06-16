<?php

namespace Sodaho\ApiRouter;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Sodaho\ApiRouter\Exception\CacheDirectoryException;
use Sodaho\ApiRouter\Exception\RouteNotFoundException;

class Router implements RequestHandlerInterface
{
    private Dispatcher $dispatcher;
    /** @var array<string, string> Map of route names to their patterns. */
    private array $namedRoutes = [];

    /**
     * @param callable $routeDefinitionCallback
     * @param array $options
     * @param ContainerInterface|null $container
     * @throws CacheDirectoryException
     */
    public function __construct(callable $routeDefinitionCallback, array $options = [], ?ContainerInterface $container = null)
    {
        $options = array_merge([
            'cacheFile' => null,
            'cacheDisabled' => false,
            'basePath' => '',
        ], $options);

        // Dispatch data structure: [dispatchable_routes, named_routes_map]
        if (!$options['cacheDisabled'] && $options['cacheFile'] && file_exists($options['cacheFile'])) {
            $cachedData = require $options['cacheFile'];
            $dispatchData = $cachedData[0];
            $this->namedRoutes = $cachedData[1];
        } else {
            $routeCollector = new RouteCollector();
            $routeDefinitionCallback($routeCollector);
            [$dispatchData, $this->namedRoutes] = $this->prepareRouteData($routeCollector->getRoutes());

            if (!$options['cacheDisabled'] && $options['cacheFile']) {
                $cacheDir = dirname($options['cacheFile']);
                if (!is_dir($cacheDir) && !mkdir($cacheDir, 0777, true) && !is_dir($cacheDir)) {
                    throw new CacheDirectoryException(sprintf('Directory "%s" was not created', $cacheDir));
                }
                $dataToCache = [$dispatchData, $this->namedRoutes];
                file_put_contents($options['cacheFile'], '<?php return ' . var_export($dataToCache, true) . ';');
            }
        }

        $this->dispatcher = new Dispatcher($dispatchData, $options['basePath'], $container);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->dispatcher->handle($request);
    }

    /**
     * Generate a URL for a named route.
     *
     * @param string $name The name of the route.
     * @param array<string, mixed> $params The parameters for the route.
     * @return string The generated URL.
     * @throws RouteNotFoundException
     */
    public function generate(string $name, array $params = []): string
    {
        if (!isset($this->namedRoutes[$name])) {
            throw new RouteNotFoundException("No route with the name '{$name}' has been defined.");
        }

        $routePattern = $this->namedRoutes[$name];

        // Replace placeholders with provided parameters
        $url = preg_replace_callback('/\{([a-zA-Z_][a-zA-Z0-9_-]*)(:[^}]+)?\}/', function ($matches) use ($params, $name) {
            $paramName = $matches[1];
            if (!isset($params[$paramName])) {
                throw new \InvalidArgumentException("Missing required parameter '{$paramName}' for route '{$name}'.");
            }
            return $params[$paramName];
        }, $routePattern);

        return $url;
    }

    private function prepareRouteData(array $routes): array
    {
        $staticRoutes = [];
        $variableRoutes = [];
        $namedRoutes = [];
        $routeParser = new RouteParser();

        foreach ($routes as $routeData) {
            if (isset($routeData['name'])) {
                $namedRoutes[$routeData['name']] = $routeData['route'];
            }

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

        return [[$staticRoutes, $variableRoutes], $namedRoutes];
    }
}
