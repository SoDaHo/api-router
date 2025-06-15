<?php

use Sodaho\ApiRouter\RouteCollector;
use Sodaho\ApiRouter\Controllers\ApiController;
use Sodaho\ApiRouter\Middleware\ExampleAuthMiddleware;
use Sodaho\ApiRouter\Middleware\PermissionMiddleware;

/**
 * Defines the API routes for the application.
 */
return function(RouteCollector $r) {

    // Top-level group for all API routes, prefixed with /api
    $r->group('/api', function(RouteCollector $r) {

        // --- 1. Public Route ---
        // Accessible by anyone.
        $r->get('/status', [ApiController::class, 'status']);

        // --- 2. Authenticated Routes ---
        // All routes within this group require a valid login token.
        $r->middlewareGroup(ExampleAuthMiddleware::class, function(RouteCollector $r) {

            // --- 2a. User-specific Routes ---
            // A nested group for all routes related to the user, prefixed with /user.
            // Full path will be /api/user/...
            $r->group('/user', function(RouteCollector $r) {

                // This route handles GET /api/user/me
                // It's available to any authenticated user.
                $r->get('/me', function (\Psr\Http\Message\ServerRequestInterface $request) {
                    // The 'user' attribute is set by the ExampleAuthMiddleware.
                    $user = $request->getAttribute('user', 'Guest');
                    return new \Sodaho\ApiRouter\Http\JsonResponse([
                        'message' => 'Authenticated user profile data',
                        'user' => $user
                    ]);
                });

                // You could add more user-specific routes here, e.g., /api/user/settings
                // $r->put('/settings', [UserController::class, 'updateSettings']);

            }); // End of /user group

            // --- 2b. Product Routes (example) ---
            $r->group('/products', function (RouteCollector $r) {

                // Handles GET /api/products, requires 'product.list' permission
                $r->get('', [ApiController::class, 'getProducts'])
                    ->middleware(PermissionMiddleware::class, 'product.list');

                // Handles GET /api/products/{id}, requires 'product.view' permission
                $r->get('/{id:\d+}', [ApiController::class, 'getProductById'])
                    ->middleware(PermissionMiddleware::class, 'product.view');
            });

        }); // End of authentication middleware group
    }); // End of /api group
};
