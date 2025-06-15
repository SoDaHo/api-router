<?php

use Sodaho\ApiRouter\RouteCollector;
use Sodaho\ApiRouter\Controllers\ApiController;
use Sodaho\ApiRouter\Controllers\UserController;
use Sodaho\ApiRouter\Controllers\AdminController;
use Sodaho\ApiRouter\Middleware\ExampleAuthMiddleware;
use Sodaho\ApiRouter\Middleware\PermissionMiddleware;

/**
 * Defines the API routes for the application.
 * This file serves as a comprehensive example of all available routing features.
 */
return function(RouteCollector $r) {

    // Use a top-level group for the '/api' prefix for all routes in this file.
    $r->group('/api', function(RouteCollector $r) {

        // --- 1. Public Routes ---
        // These routes have no middleware and are accessible by anyone.
        $r->get('/status', [ApiController::class, 'status']);


        // --- 2. Authenticated User Routes ---
        // This group requires a user to be authenticated. The ExampleAuthMiddleware
        // is applied to all routes defined within this block.
        $r->middlewareGroup(ExampleAuthMiddleware::class, function(RouteCollector $r) {

            // A simple route for any authenticated user.
            $r->get('/me', [UserController::class, 'showMyProfile']);

            // A route with a single, specific permission check.
            // The chained ->middleware() is applied AFTER the group middleware.
            $r->get('/posts', [ApiController::class, 'listPosts'])
                ->middleware(PermissionMiddleware::class, 'posts.list');

            // A route with multiple, chained permission checks.
            // The user must have BOTH 'posts.create' AND 'posts.upload_image' permissions.
            $r->post('/posts', [ApiController::class, 'createPost'])
                ->middleware(PermissionMiddleware::class, 'posts.create')
                ->middleware(PermissionMiddleware::class, 'posts.upload_image');


            // --- 3. Admin-Only Routes ---
            // This nested group demonstrates how to apply a permission check to a
            // whole group of routes using the new parametrisierte middlewareGroup.
            // A user must already be authenticated (from the parent group) AND
            // must have the 'admin.access' permission.
            $r->middlewareGroup([PermissionMiddleware::class, 'admin.access'], function(RouteCollector $r) {

                // All routes here automatically require 'admin.access'.
                $r->get('/admin/dashboard', [AdminController::class, 'dashboard']);
                $r->get('/admin/users', [AdminController::class, 'listUsers']);

                // This admin route has an ADDITIONAL, more specific permission check.
                // The user needs 'admin.access' (from the group) AND 'users.delete' (from the route).
                $r->delete('/admin/users/{id}', [AdminController::class, 'deleteUser'])
                    ->middleware(PermissionMiddleware::class, 'users.delete');
            });
        });
    });
};
