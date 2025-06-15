<?php

namespace Sodaho\ApiRouter\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sodaho\ApiRouter\RouteParser;

class RouteParserTest extends TestCase
{
    #[Test]
    public function it_parses_a_simple_static_route(): void
    {
        // 1. Arrange: Create the object we want to test.
        $parser = new RouteParser();

        // 2. Act: Call the method we want to test.
        [$regex, $variables] = $parser->parse('/users');

        // 3. Assert: Check if the result is what we expect.
        $this->assertSame('/users', $regex);
        $this->assertEmpty($variables);
    }

    #[Test]
    public function it_parses_a_route_with_a_simple_placeholder(): void
    {
        // Arrange
        $parser = new RouteParser();

        // Act
        [$regex, $variables] = $parser->parse('/user/{name}');

        // Assert
        $this->assertSame('/user/([^/]+)', $regex);
        $this->assertSame(['name'], $variables);
    }

    #[Test]
    public function it_parses_a_route_with_a_regex_constraint(): void
    {
        // Arrange
        $parser = new RouteParser();

        // Act
        [$regex, $variables] = $parser->parse('/user/{id:\d+}');

        // Assert
        $this->assertSame('/user/(\d+)', $regex);
        $this->assertSame(['id'], $variables);
    }
}
