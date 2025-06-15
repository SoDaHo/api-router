<?php

namespace Sodaho\ApiRouter\Middleware;

class PermissionMiddleware implements MiddlewareInterface
{
    /** @var string The required permission */
    protected string $permission;

    /**
     * @param string $permission The permission required to pass this middleware.
     */
    public function __construct(string $permission)
    {
        $this->permission = $permission;
    }

    /**
     * Handles the permission check.
     */
    public function handle(): void
    {
        // This is a simulation. In a real app, you would:
        // 1. Get the authenticated user (presumably by a previous AuthMiddleware).
        // 2. Check if that user has the required permission.
        // e.g., if (!Auth::user()->hasPermission($this->permission)) { ... }

        // For this example, we simulate checking a specific header.
        $userPermissionHeader = $_SERVER['HTTP_X_USER_PERMISSIONS'] ?? '';
        $userPermissions = explode(',', $userPermissionHeader);

        if (!in_array($this->permission, $userPermissions, true)) {
            http_response_code(403); // Forbidden
            header('Content-Type: application/json');
            echo json_encode(['error' => "Forbidden: Missing required permission '{$this->permission}'"]);
            exit;
        }
    }
}