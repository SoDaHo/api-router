<?php

namespace Sodaho\ApiRouter\Controllers;

use Psr\Http\Message\ResponseInterface;
use Sodaho\ApiRouter\Http\JsonResponse;

class ApiController
{
    public function status(): ResponseInterface
    {
        return new JsonResponse(['status' => 'ok', 'timestamp' => time()]);
    }

    public function getProducts(): ResponseInterface
    {
        $products = [
            ['id' => 1, 'name' => 'Laptop'],
            ['id' => 2, 'name' => 'Mouse'],
        ];
        return new JsonResponse($products);
    }

    public function getProductById(string $id): ResponseInterface
    {
        return new JsonResponse(['id' => (int)$id, 'name' => 'Sample Product']);
    }
}