<?php

namespace Sodaho\ApiRouter\Http;

use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;

class JsonResponse extends Response
{
    public function __construct(mixed $data, int $status = 200, array $headers = [])
    {
        parent::__construct(
            $status,
            array_merge(['Content-Type' => 'application/json'], $headers),
            json_encode($data)
        );
    }
}