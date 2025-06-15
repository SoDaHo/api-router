<?php

namespace Sodaho\ApiRouter\Middleware;

/**
 * Interface MiddlewareInterface
 *
 * Defines the contract for all middleware classes.
 */
interface MiddlewareInterface
{
    /**
     * Handle an incoming request.
     *
     * This method can perform actions before the request reaches the controller.
     * If this method does not explicitly stop execution (e.g., by throwing an
     * exception or exiting), the request will proceed to the next middleware
     * or the final handler.
     *
     * @return void
     */
    public function handle(): void;
}