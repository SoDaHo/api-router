<?php

use Sodaho\ApiRouter\RouteCollector;
use Sodaho\ApiRouter\Controllers\ApiController;
use Sodaho\ApiRouter\Middleware\ExampleAuthMiddleware;

/**
 * Defines the API routes for the application.
 */
return function(RouteCollector $r) {
    $r->group('/api', function(RouteCollector $r) {

        $r->get('/status', [ApiController::class, 'status']);

        $r->middleware(ExampleAuthMiddleware::class, function(RouteCollector $r) {
            $r->get('/products', [ApiController::class, 'getProducts']);
            $r->get('/products/{id:\d+}', [ApiController::class, 'getProductById']);
        });
    });
};