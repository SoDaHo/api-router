<?php

require __DIR__ . '/../vendor/autoload.php';

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;
use Psr\Container\ContainerInterface;
use Sodaho\ApiRouter\Router;
use Sodaho\ApiRouter\Controllers\HomeController;

// --- A Simple DI Container for Demonstration ---
class DemoContainer implements ContainerInterface {
    private array $entries = [];
    public function get(string $id) {
        if (!$this->has($id)) {
            // In a real container, this would use reflection for auto-wiring.
            // For this demo, we assume manual definition is required.
            throw new \Exception("Service not found: $id");
        }
        $entry = $this->entries[$id];
        return $entry($this); // Pass container for nested dependencies
    }
    public function has(string $id): bool {
        return isset($this->entries[$id]);
    }
    public function set(string $id, callable $callable): void {
        $this->entries[$id] = $callable;
    }
}

// 1. Create a PSR-7 Request object from globals
$psr17Factory = new Psr17Factory();
$creator = new ServerRequestCreator($psr17Factory, $psr17Factory, $psr17Factory, $psr17Factory);
$request = $creator->fromGlobals();

// --- Application Configuration ---
$cacheDir = __DIR__ . '/../cache';
$basePath = '';
$isProduction = false; // Set to true on production
// --- End Configuration ---

// --- Dependency Injection Container Setup ---
$container = new DemoContainer();
// Define how to create the HomeController. In a real app, this would be more complex.
$container->set(HomeController::class, fn(ContainerInterface $c) => new HomeController());
// Add other controllers and services here...
// $container->set(Database::class, fn(ContainerInterface $c) => new Database('dsn...'));
// $container->set(ApiController::class, fn(ContainerInterface $c) => new ApiController($c->get(Database::class)));


// --- Routing Setup ---
$uri = $request->getUri()->getPath();
$isApiRequest = str_starts_with($uri, $basePath . '/api');
$routesFile = $isApiRequest ? __DIR__ . '/../routes/api.php' : __DIR__ . '/../routes/web.php';
$cacheFile = $isApiRequest ? $cacheDir . '/api_routes.php' : $cacheDir . '/web_routes.php';


try {
    // 2. Create the main application router, injecting the container
    $app = new Router(require $routesFile, [
        'cacheFile' => $cacheFile,
        'cacheDisabled' => !$isProduction,
        'basePath' => $basePath,
    ], $container); // Pass the container here

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
    $response->getBody()->write(json_encode($errorData, JSON_PRETTY_PRINT));
}

// 4. Emit the response to the browser
if (!headers_sent()) {
    http_response_code($response->getStatusCode());
    foreach ($response->getHeaders() as $name => $values) {
        foreach ($values as $value) {
            header(sprintf('%s: %s', $name, $value), false);
        }
    }
}
echo $response->getBody();
