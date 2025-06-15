<?php

use Sodaho\ApiRouter\RouteCollector;
use Sodaho\ApiRouter\Controllers\HomeController;

/**
 * Defines the web routes for the application.
 */
return function(RouteCollector $r) {
    $r->get('/', [HomeController::class, 'index']);

    $r->get('/user/{name}', [HomeController::class, 'showUser']);

    $r->get('/user/{id:\d+}', [HomeController::class, 'showUserById']);

    // Example of how you could add middleware to a web route in the future
    // $r->middleware(SomeWebMiddleware::class, function(RouteCollector $r) {
    //     $r->get('/dashboard', [DashboardController::class, 'index']);
    // });
};