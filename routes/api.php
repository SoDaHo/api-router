<?php

use Sodaho\ApiRouter\RouteCollector;
use Sodaho\ApiRouter\Controllers\ApiController;
use Sodaho\ApiRouter\Middleware\ExampleAuthMiddleware;
use Sodaho\ApiRouter\Middleware\PermissionMiddleware;

/**
 * Defines the API routes for the application.
 */
return function(RouteCollector $r) {

    $r->group('/api', function(RouteCollector $r) {

        $r->get('/status', [ApiController::class, 'status']);

        $r->middlewareGroup(ExampleAuthMiddleware::class, function(RouteCollector $r) {

            // Ein einfacher Test für eine geschützte Route
            $r->get('/me', function (\Psr\Http\Message\ServerRequestInterface $request) {
                $user = $request->getAttribute('user', 'Guest'); // Holt Attribut aus AuthMiddleware
                return new \Sodaho\ApiRouter\Http\JsonResponse(['user' => $user]);
            });

            // Route, um alle Produkte aufzulisten (benötigt 'product.list' Berechtigung)
            $r->get('/products', [ApiController::class, 'getProducts'])
                ->middleware(PermissionMiddleware::class, 'product.list');

            // Route, um ein einzelnes Produkt anzusehen (benötigt 'product.view' Berechtigung)
            $r->get('/products/{id:\d+}', [ApiController::class, 'getProductById'])
                ->middleware(PermissionMiddleware::class, 'product.view');
        });
    });
};