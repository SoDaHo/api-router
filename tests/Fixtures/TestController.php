<?php

namespace Sodaho\ApiRouter\Tests\Fixtures;

use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;

class TestController
{
    public function index(): ResponseInterface
    {
        return new Response(200, [], 'Hello from TestController');
    }
}