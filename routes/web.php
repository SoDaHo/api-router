<?php

use SoDaHo\ApiRouter\RouteCollector;
use SoDaHo\ApiRouter\Controllers\HomeController; // In a real app, this would be your own controller namespace.

/**
 * Defines the web routes for the application.
 *
 * @param RouteCollector $r The RouteCollector instance.
 * @return void
 */
return function(RouteCollector $r) {
    $r->get('/', [HomeController::class, 'index']);

    $r->get('/user/{name}', [HomeController::class, 'showUser']);

    $r->get('/user/{id:\d+}', [HomeController::class, 'showUserById']);
};
