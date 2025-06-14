<?php

namespace Sodaho\ApiRouter\Controllers;

/**
 * Class HomeController
 *
 * Example controller for handling web-facing routes.
 * NOTE: This file is for demonstration purposes and would typically
 * reside in the user's application, not in the router library itself.
 */
class HomeController
{
    public function index(): void
    {
        echo '<h1>Home Page</h1><p>Loaded from HomeController!</p>';
    }

    public function showUser(string $name): void
    {
        echo "<h1>Hello, " . htmlspecialchars($name) . "!</h1><p>Loaded from HomeController.</p>";
    }

    public function showUserById(string $id): void
    {
        echo "<h1>User Profile</h1><p>User ID: {$id}</p><p>Loaded from HomeController.</p>";
    }
}
