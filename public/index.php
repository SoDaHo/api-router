<?php

/**
 * Public entry point for the application.
 *
 * This file handles incoming requests, determines which routes to load,
 * invalidates the cache if necessary, and dispatches the request.
 */

// Bootstrap the application by loading the Composer autoloader.
require __DIR__ . '/../vendor/autoload.php';

use Sodaho\ApiRouter\Dispatcher;
use Sodaho\ApiRouter\Router;

$routesDir = __DIR__ . '/../routes';
$cacheDir = __DIR__ . '/../cache';

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';

// Decide which route file to use based on the URI prefix.
// This allows separating web and API routes.
if (str_starts_with($uri, '/api')) {
    $routesFile = $routesDir . '/api.php';
    $cacheFile = $cacheDir . '/api_routes.php';
} else {
    $routesFile = $routesDir . '/web.php';
    $cacheFile = $cacheDir . '/web_routes.php';
}

// A simple cache invalidation strategy:
// If the routes file has been modified more recently than the cache file, delete the cache.
if (file_exists($routesFile) && file_exists($cacheFile) && filemtime($cacheFile) < filemtime($routesFile)) {
    unlink($cacheFile);
}

if (!file_exists($routesFile)) {
    header("HTTP/1.0 500 Internal Server Error");
    die("Route definition file not found: " . htmlspecialchars($routesFile));
}

// Create the dispatcher instance using the Router facade.
$routeDefinitionCallback = require $routesFile;
$dispatcher = Router::createDispatcher($routeDefinitionCallback, [
    'cacheFile' => $cacheFile,
]);

$httpMethod = $_SERVER['REQUEST_METHOD'];
$routeInfo = $dispatcher->dispatch($httpMethod, $uri);

// Process the result from the dispatcher.
switch ($routeInfo[0]) {
    case Dispatcher::NOT_FOUND:
        http_response_code(404);
        echo '<h1>404 Not Found</h1>';
        break;

    case Dispatcher::METHOD_NOT_ALLOWED:
        $allowedMethods = $routeInfo[1];
        http_response_code(405);
        header("Allow: " . implode(', ', $allowedMethods));
        echo '<h1>405 Method Not Allowed</h1>';
        break;

    case Dispatcher::FOUND:
        [/* status */, $handler, $vars] = $routeInfo;

        // Handle Closure-based routes.
        if (is_callable($handler)) {
            call_user_func_array($handler, $vars);
            break;
        }

        // Handle [Controller::class, 'method'] routes.
        if (is_array($handler) && count($handler) === 2 && class_exists($handler[0])) {
            $controllerClass = $handler[0];
            $method = $handler[1];

            $controller = new $controllerClass();

            if (method_exists($controller, $method)) {
                call_user_func_array([$controller, $method], $vars);
                break;
            }
        }

        // Fallback for invalid handler configuration.
        http_response_code(500);
        echo '<h1>500 Internal Server Error</h1><p>The route handler is not configured correctly.</p>';
        break;
}
