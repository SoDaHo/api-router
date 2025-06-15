<?php

require __DIR__ . '/../vendor/autoload.php';

use Sodaho\ApiRouter\Router;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;

// 1. Create a PSR-7 Request object from globals
$psr17Factory = new Psr17Factory();
$creator = new ServerRequestCreator($psr17Factory, $psr17Factory, $psr17Factory, $psr17Factory);
$request = $creator->fromGlobals();

// --- Application Configuration ---
$cacheDir = __DIR__ . '/../cache';
$basePath = '';
$isProduction = false; // Set to true on production
// --- End Configuration ---

// Logic to determine which route file to load
$uri = $request->getUri()->getPath();
$isApiRequest = str_starts_with($uri, $basePath . '/api');
$routesFile = $isApiRequest ? __DIR__ . '/../routes/api.php' : __DIR__ . '/../routes/web.php';
$cacheFile = $isApiRequest ? $cacheDir . '/api_routes.php' : $cacheDir . '/web_routes.php';

// ... Cache invalidation logic ...

try {
    // 2. Create the main application router (which is a RequestHandler)
    $app = new Router(require $routesFile, [
        'cacheFile' => $cacheFile,
        'cacheDisabled' => !$isProduction,
        'basePath' => $basePath,
    ]);

    // 3. Handle the request and get a response
    $response = $app->handle($request);

} catch (\Throwable $e) {
    // Basic error handling
    $response = $psr17Factory->createResponse(500)->withHeader('Content-Type', 'application/json');
    $errorData = ['error' => 'Internal Server Error'];
    if (!$isProduction) {
        $errorData['message'] = $e->getMessage();
        $errorData['trace'] = $e->getTrace();
    }
    $response->getBody()->write(json_encode($errorData));
}

// 4. Emit the response to the browser
http_response_code($response->getStatusCode());
foreach ($response->getHeaders() as $name => $values) {
    foreach ($values as $value) {
        header(sprintf('%s: %s', $name, $value), false);
    }
}
echo $response->getBody();