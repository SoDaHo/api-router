<?php

use SoDaHo\ApiRouter\RouteCollector;
use SoDaHo\ApiRouter\Controllers\ApiController; // In a real app, this would be your own controller namespace.

/**
 * Defines the API routes for the application.
 *
 * @param RouteCollector $r The RouteCollector instance.
 * @return void
 */
return function(RouteCollector $r) {
    // It's a common practice to group all API routes under a prefix.
    $r->group('/api', function(RouteCollector $r) {
        $r->get('/status', [ApiController::class, 'status']);
        $r->get('/products', [ApiController::class, 'getProducts']);
        $r->get('/products/{id:\d+}', [ApiController::class, 'getProductById']);
    });
};
