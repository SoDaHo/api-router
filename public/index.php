<?php

/**
 * Public entry point for the application.
 */

require __DIR__ . '/../vendor/autoload.php';

use Sodaho\ApiRouter\Dispatcher;
use Sodaho\ApiRouter\Router;

// --- Application Configuration ---
$cacheDir = __DIR__ . '/../cache';
$basePath = '';
// --- End Configuration ---


$routesDir = __DIR__ . '/../routes';
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';

$isApiRequest = str_starts_with($uri, $basePath . '/api');

if ($isApiRequest) {
    $routesFile = $routesDir . '/api.php';
    $cacheFile = $cacheDir . '/api_routes.php';
} else {
    $routesFile = $routesDir . '/web.php';
    $cacheFile = $cacheDir . '/web_routes.php';
}

if (file_exists($routesFile) && file_exists($cacheFile) && filemtime($cacheFile) < filemtime($routesFile)) {
    unlink($cacheFile);
}

if (!file_exists($routesFile)) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Internal Server Error', 'message' => 'Route definition file not found.']);
    exit;
}


try {
    $routeDefinitionCallback = require $routesFile;
    $dispatcher = Router::createDispatcher($routeDefinitionCallback, [
        'cacheFile' => $cacheFile,
        'basePath' => $basePath,
    ]);

    $httpMethod = $_SERVER['REQUEST_METHOD'];
    $routeInfo = $dispatcher->dispatch($httpMethod, $uri);

    switch ($routeInfo[0]) {
        case Dispatcher::NOT_FOUND:
            http_response_code(404);
            if ($isApiRequest) {
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Not Found']);
            } else {
                echo '<h1>404 Not Found</h1>';
            }
            break;

        case Dispatcher::METHOD_NOT_ALLOWED:
            $allowedMethods = $routeInfo[1];
            http_response_code(405);
            header('Allow: ' . implode(', ', $allowedMethods));
            if ($isApiRequest) {
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Method Not Allowed', 'allowed' => $allowedMethods]);
            } else {
                echo '<h1>405 Method Not Allowed</h1>';
            }
            break;

        case Dispatcher::FOUND:
            [/* status */, $handler, $vars, $middleware] = $routeInfo;

            $dispatcher->runMiddleware($middleware);

            if (is_array($handler) && count($handler) === 2 && class_exists($handler[0])) {
                $controllerClass = $handler[0];
                $method = $handler[1];
                $controller = new $controllerClass();

                if (method_exists($controller, $method)) {
                    call_user_func_array([$controller, $method], $vars);
                    break;
                }
            }

            http_response_code(500);
            if ($isApiRequest) {
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Internal Server Error', 'message' => 'Invalid route handler configuration.']);
            } else {
                echo '<h1>500 Internal Server Error</h1><p>The route handler is not configured correctly.</p>';
            }
            break;
    }

} catch (\Exception $e) {
    http_response_code(500);
    error_log($e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Internal Server Error', 'message' => 'An error occurred.']);
}