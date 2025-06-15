<?php

namespace Sodaho\ApiRouter\Controllers;

use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;

class HomeController
{
    public function index(): ResponseInterface
    {
        $html = '<h1>Home Page</h1><p>Loaded from HomeController!</p>';
        $response = new Response();
        $response->getBody()->write($html);
        return $response;
    }

    public function showUser(string $name): ResponseInterface
    {
        $html = "<h1>Hello, " . htmlspecialchars($name) . "!</h1><p>Loaded from HomeController.</p>";
        $response = new Response();
        $response->getBody()->write($html);
        return $response;
    }

    public function showUserById(string $id): ResponseInterface
    {
        $html = "<h1>User Profile</h1><p>User ID: {$id}</p><p>Loaded from HomeController.</p>";
        $response = new Response();
        $response->getBody()->write($html);
        return $response;
    }
}