<?php

declare(strict_types=1);

namespace App\Tests;

use App\Middleware\AuthenticationMiddleware;
use DI\Container;
use Odan\Session\PhpSession;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Message\ResponseInterface as Response;

#[CoversClass(AuthenticationMiddleware::class)]
final class AuthenticationMiddlewareTest extends TestCase
{
    /**
     * Helper function to test runs where the handler is invoked
     * @param Container $container
     * @param PhpSession $session
     * @return void
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     */
    private function testAuthWithReturn(Container $container, PhpSession $session): void
    {
        $responseMock = $this->getMockBuilder(Response::class)->getMock();
        $requestMock = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->getMock();

        $handlerMock = $this->getMockBuilder(RequestHandler::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['handle'])
            ->getMock();
        $handlerMock
            ->expects($this->once())
            ->method('handle')
            ->with($this->equalTo($requestMock))
            ->willReturn($responseMock);
        $dut = new AuthenticationMiddleware($container, $session);
        $response = $dut($requestMock, $handlerMock);
        $this->assertEquals($response, $responseMock);
    }

    /**
     * Test that the middleware allows access when no password is configured
     * @return void
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     */
    public function testUnconfiguredAuth(): void
    {
        $containerMock = $this->getMockBuilder(\DI\Container::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['has'])
            ->getMock();
        $containerMock
            ->expects($this->once())
            ->method('has')
            ->willReturn(false);

        $this->testAuthWithReturn($containerMock, new PhpSession());
    }

    /**
     * Test that the middleware allows access when the password is actively disabled
     * @return void
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     */
    public function testDisabledAuth(): void
    {
        $containerMock = $this->getMockBuilder(\DI\Container::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['has', 'get'])
            ->getMock();
        $containerMock
            ->expects($this->once())
            ->method('has')
            ->willReturn(true);
        $containerMock
            ->expects($this->once())
            ->method('get')
            ->willReturn(null);

        $this->testAuthWithReturn($containerMock, new PhpSession());
    }

    /**
     * Test that the middleware redirects to the login page when the password is configured
     * and the user is not logged in
     * @return void
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     */
    public function testEnabledAuthUnauthorized(): void
    {
        $containerMock = $this->getMockBuilder(\DI\Container::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['has', 'get'])
            ->getMock();
        $containerMock
            ->expects($this->once())
            ->method('has')
            ->willReturn(true);
        $containerMock
            ->expects($this->once())
            ->method('get')
            ->willReturn('phpunit');

        $requestMock = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->getMock();

        $handlerMock = $this->getMockBuilder(RequestHandler::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dut = new AuthenticationMiddleware($containerMock, new PhpSession());
        $response = $dut($requestMock, $handlerMock);
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('/login', $response->getHeaderLine('Location'));
    }

    /**
     * Test that the middleware allows access when authentication is enabled and the user is authorized.
     * @return void
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     */
    public function testEnabledAuthAuthorized(): void
    {
        $containerMock = $this->getMockBuilder(\DI\Container::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['has', 'get'])
            ->getMock();
        $containerMock
            ->expects($this->once())
            ->method('has')
            ->willReturn(true);
        $containerMock
            ->expects($this->once())
            ->method('get')
            ->willReturn('phpunit');

        $session = new PhpSession();
        $session->set('loggedIn', true);

        $this->testAuthWithReturn($containerMock, $session);
    }
}
