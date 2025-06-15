<?php

namespace Sodaho\ApiRouter\Middleware;

/**
 * Class ExampleAuthMiddleware
 *
 * An example middleware that simulates checking for an authentication token.
 */
class ExampleAuthMiddleware implements MiddlewareInterface
{
    /**
     * Handles the authentication check.
     */
    public function handle(): void
    {
        // In a real application, you would inspect the request for a valid
        // session, JWT, or API token.
        if (!isset($_SERVER['HTTP_AUTHORIZATION']) || empty($_SERVER['HTTP_AUTHORIZATION'])) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Unauthorized']);
            // Stop further execution
            exit;
        }
    }
}