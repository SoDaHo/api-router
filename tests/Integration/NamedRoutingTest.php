<?php
// tests/Integration/NamedRoutingTest.php
namespace Sodaho\ApiRouter\Tests\Integration;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sodaho\ApiRouter\Exception\RouteNotFoundException;
use Sodaho\ApiRouter\Router;
use Sodaho\ApiRouter\Tests\Fixtures\TestController;

class NamedRoutingTest extends TestCase
{
    #[Test]
    public function it_generates_url_for_simple_static_route(): void
    {
        // Arrange
        $app = new Router(function ($r) {
            $r->get('/home', [TestController::class, 'index'])->name('home.page');
        }, ['cacheDisabled' => true]);

        // Act
        $url = $app->generate('home.page');

        // Assert
        $this->assertSame('/home', $url);
    }

    #[Test]
    public function it_generates_url_for_route_with_one_parameter(): void
    {
        // Arrange
        $app = new Router(function ($r) {
            $r->get('/user/{id}', [TestController::class, 'index'])->name('user.show');
        }, ['cacheDisabled' => true]);

        // Act
        $url = $app->generate('user.show', ['id' => 123]);

        // Assert
        $this->assertSame('/user/123', $url);
    }

    #[Test]
    public function it_generates_url_for_route_with_regex_parameter(): void
    {
        // Arrange
        $app = new Router(function ($r) {
            $r->get('/product/{id:\d+}', [TestController::class, 'index'])->name('product.view');
        }, ['cacheDisabled' => true]);

        // Act
        $url = $app->generate('product.view', ['id' => 456]);

        // Assert
        $this->assertSame('/product/456', $url);
    }

    #[Test]
    public function it_generates_url_for_route_with_multiple_parameters(): void
    {
        // Arrange
        $app = new Router(function ($r) {
            $r->get('/articles/{category}/{slug}', [TestController::class, 'index'])->name('article.show');
        }, ['cacheDisabled' => true]);

        // Act
        $url = $app->generate('article.show', ['category' => 'php', 'slug' => 'dependency-injection']);

        // Assert
        $this->assertSame('/articles/php/dependency-injection', $url);
    }

    #[Test]
    public function it_throws_exception_for_unknown_route_name(): void
    {
        // Arrange
        $app = new Router(fn($r) => null, ['cacheDisabled' => true]);

        // Assert
        $this->expectException(RouteNotFoundException::class);
        $this->expectExceptionMessage("No route with the name 'non.existent.route' has been defined.");

        // Act
        $app->generate('non.existent.route');
    }

    #[Test]
    public function it_throws_exception_if_required_parameter_is_missing(): void
    {
        // Arrange
        $app = new Router(function ($r) {
            $r->get('/user/{id}', [TestController::class, 'index'])->name('user.show');
        }, ['cacheDisabled' => true]);

        // Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Missing required parameter 'id' for route 'user.show'.");

        // Act
        $app->generate('user.show'); // Missing 'id' parameter
    }
}
