# Sodaho/ApiRouter

A lightweight, performant, and modern PHP router built on the core concepts of `nikic/FastRoute`. It is fully PSR-7 and PSR-15 compliant, offering a clean, extensible architecture for modern PHP applications.

[![Latest Version](https://img.shields.io/badge/version-1.0.0-blue.svg)](https://github.com/sodaho/api-router)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg)](LICENSE.md)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.2-8892BF.svg)](https://php.net)

---

## Features

- **PSR-7 & PSR-15 Compliant:** Utilizes standard HTTP-Message and Middleware interfaces for full interoperability.
- **High-Performance Routing:** Separates static and dynamic (regex-based) routes for maximum speed.
- **Clean Route Syntax:** Supports placeholders (`/user/{id}`) and regex constraints (`/user/{id:\d+}`).
- **Powerful Middleware System:** Middleware can be applied to single routes or entire groups.
- **Dependency Injection Ready:** Integrates with any PSR-11 compatible DI container.
- **Named Routes:** Simplifies URL generation and increases maintainability.
- **Route Caching:** Compiles routes for maximum performance in production environments.

## Installation

Install the package via Composer.

```bash
composer require sodaho/api-router
```

## Quick Start

Create a `public/index.php` as your entry point and `routes/web.php` for your route definitions.

**public/index.php**
```php
<?php

require __DIR__ . '/../vendor/autoload.php';

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;
use Sodaho\ApiRouter\Router;

// Create a PSR-7 Request
$psr17Factory = new Psr17Factory();
$creator = new ServerRequestCreator($psr17Factory, $psr17Factory, $psr17Factory, $psr17Factory);
$request = $creator->fromGlobals();

// Instantiate the router
$app = new Router(require __DIR__ . '/../routes/web.php');

// Handle the request and emit the response
$response = $app->handle($request);

http_response_code($response->getStatusCode());
foreach ($response->getHeaders() as $name => $values) {
    header(sprintf('%s: %s', $name, reset($values)), false);
}
echo $response->getBody();
```

**routes/web.php**
```php
<?php

use Sodaho\ApiRouter\RouteCollector;
use App\Controllers\HomeController;

return function(RouteCollector $r) {
    $r->get('/', [HomeController::class, 'index']);
    $r->get('/hello/{name}', [HomeController::class, 'hello']);
};
```

## Defining Routes

### HTTP Methods
Supports `get`, `post`, `put`, `delete`, `patch`, `head`, and `options`.

```php
$r->get('/users', [UserController::class, 'index']);
$r->post('/users', [UserController::class, 'create']);
```

### Route Parameters
Define dynamic parts of the URL using curly braces.

```php
$r->get('/user/{id}', [UserController::class, 'show']);

// Controller Method
public function show(ServerRequestInterface $request, string $id)
{
    // $id contains the value from the URL
}
```

### Regex Constraints
Add a regex pattern directly in the placeholder to validate it.

```php
// {id} must be a digit
$r->get('/user/{id:\d+}', [UserController::class, 'show']);

// {name} must only consist of letters
$r->get('/user/{name:[a-zA-Z]+}', [UserController::class, 'showByName']);
```

### Route Groups
Group routes under a common URL prefix.

```php
$r->group('/api', function (RouteCollector $r) {
    $r->get('/users', ...); // Becomes /api/users
    $r->get('/posts', ...); // Becomes /api/posts
});
```

## Middleware
Middleware (based on PSR-15) can be applied to single routes or entire groups.

### Group Middleware
Use `middlewareGroup` to apply one or more middlewares to a group.

```php
use App\Middleware\AuthMiddleware;
use App\Middleware\AdminCheckMiddleware;

$r->middlewareGroup([AuthMiddleware::class, AdminCheckMiddleware::class], function (RouteCollector $r) {
    $r->get('/dashboard', ...); // Both middlewares will be executed
    $r->get('/settings', ...);  // Both middlewares will be executed
});
```

### Middleware for Single Routes
Chain `->middleware()` to a route definition.

```php
use App\Middleware\VerifyCsrfToken;

$r->post('/profile/update', [ProfileController::class, 'update'])
  ->middleware(VerifyCsrfToken::class);
```

## Dependency Injection
Pass a PSR-11 compatible container instance to the router to resolve controllers and their dependencies automatically.

**public/index.php (with DI-Container)**
```php
// Example with PHP-DI
$containerBuilder = new \DI\ContainerBuilder();
$containerBuilder->useAutowiring(true);
$container = $containerBuilder->build();

// Instantiate the router and pass the container
$app = new Router(require __DIR__ . '/../routes/web.php', [], $container);

// ...
```

**Example Controller with Dependency**
```php
class UserController
{
    private DatabaseConnection $db;

    // The dependency is automatically injected by the container
    public function __construct(DatabaseConnection $db)
    {
        $this->db = $db;
    }

    public function show(ServerRequestInterface $request, string $id)
    {
        $user = $this->db->find('users', $id);
        // ...
    }
}
```

## Named Routes & URL Generation
Give routes a name to generate URLs in a maintainable and centralized way.

### Naming a Route
Chain `->name()` to a route definition.

```php
$r->get('/user/{id:\d+}', [UserController::class, 'show'])->name('user.profile');
```

### Generating a URL
Use the router's `generate()` method.

```php
// Assuming $router is your router instance

// Generates: /user/123
$profileUrl = $router->generate('user.profile', ['id' => 123]);

echo "<a href=\"{$profileUrl}\">View Profile</a>";
```
This is extremely useful to avoid hard-coding URLs in templates and code.

## Configuration
You can customize the router's behavior via an options array in the constructor.

```php
$options = [
    // Disables caching, useful for development
    'cacheDisabled' => true, 
    
    // Path to the cache file
    'cacheFile' => __DIR__ . '/../cache/routes.php',
    
    // Base path if the app runs in a subdirectory
    'basePath' => '/my-app',
];

$app = new Router(..., $options);
```

## License
The project is licensed under the MIT License.