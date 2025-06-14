<?php

namespace Sodaho\ApiRouter\Controllers;

/**
 * Class ApiController
 *
 * Example controller for handling API routes.
 * NOTE: This file is for demonstration purposes and would typically
 * reside in the user's application, not in the router library itself.
 */
class ApiController
{
    public function status(): void
    {
        $this->jsonResponse(['status' => 'ok', 'timestamp' => time()]);
    }

    public function getProducts(): void
    {
        $products = [
            ['id' => 1, 'name' => 'Laptop'],
            ['id' => 2, 'name' => 'Maus'],
        ];
        $this->jsonResponse($products);
    }

    public function getProductById(string $id): void
    {
        // In a real application, you would fetch this from a database.
        $this->jsonResponse(['id' => (int)$id, 'name' => 'Sample Product']);
    }

    /**
     * A helper method to send a JSON response.
     * @param mixed $data The data to encode as JSON.
     * @param int $statusCode The HTTP status code.
     */
    private function jsonResponse(mixed $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
    }
}
