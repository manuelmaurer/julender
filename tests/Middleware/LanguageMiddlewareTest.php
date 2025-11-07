<?php

declare(strict_types=1);

namespace App\Tests\Middleware;

use App\Middleware\LanguageMiddleware;
use DI\Container;
use DI\DependencyException;
use DI\NotFoundException;
use Odan\Session\PhpSession;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

#[CoversClass(LanguageMiddleware::class)]
final class LanguageMiddlewareTest extends TestCase
{
    /**
     * Helper function to test runs where the handler is invoked
     * @param Container $container
     * @param PhpSession $session
     * @param string|null $headerLine
     * @return void
     * @throws DependencyException
     * @throws NotFoundException
     */
    private function testMiddlewareWithResponse(Container $container, PhpSession $session, ?string $headerLine = null): void
    {
        $responseMock = $this->getMockBuilder(Response::class)->getMock();
        $requestMock = $this->getMockBuilder(\Slim\Psr7\Request::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getHeaderLine'])
            ->getMock();

        if ($headerLine !== null) {
            $requestMock
                ->expects($this->once())
                ->method('getHeaderLine')
                ->with('Accept-Language')
                ->willReturn($headerLine);
        }

        $handlerMock = $this->getMockBuilder(RequestHandler::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['handle'])
            ->getMock();
        $handlerMock
            ->expects($this->once())
            ->method('handle')
            ->with($this->equalTo($requestMock))
            ->willReturn($responseMock);
        $dut = new LanguageMiddleware($container, $session);
        $response = $dut($requestMock, $handlerMock);
        $this->assertEquals($response, $responseMock);
    }

    /**
     * Test that the middleware throws an exception when no languages are configured
     * @return void
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function testInvalidConfiguration(): void
    {
        $containerMock = $this->getMockBuilder(Container::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['get'])
            ->getMock();
        $containerMock
            ->expects($this->once())
            ->method('get')
            ->willReturn(null);
        $dut = new LanguageMiddleware($containerMock, new PhpSession());
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid configuration');
        $dut($this->getMockBuilder(Request::class)->getMock(), $this->getMockBuilder(RequestHandler::class)->getMock());
    }

    /**
     * Test that the middleware honors the session language
     * @return void
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function testSessionLanguage(): void
    {
        $containerMock = $this->getMockBuilder(Container::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['get'])
            ->getMock();
        $containerMock
            ->expects($this->once())
            ->method('get')
            ->willReturn(['de', 'en', 'fr']);
        $session = new PhpSession();
        $session->set('language', 'en');
        $this->testMiddlewareWithResponse($containerMock, $session);
    }

    /**
     * Test the browser language detection
     * @return void
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function testBrowserLanguage(): void
    {
        $containerMock = $this->getMockBuilder(Container::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['get'])
            ->getMock();
        $containerMock
            ->expects($this->once())
            ->method('get')
            ->willReturn(['de', 'en', 'fr']);
        $session = new PhpSession();
        $this->testMiddlewareWithResponse($containerMock, $session, 'en-US');
        $this->assertEquals('en', $session->get('language'));
    }

    /**
     * Test that the middleware falls back to the first configured language in case
     * everything else fails
     * @return void
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function testFallback(): void
    {
        $containerMock = $this->getMockBuilder(Container::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['get'])
            ->getMock();
        $containerMock
            ->expects($this->once())
            ->method('get')
            ->willReturn(['de', 'en', 'fr']);
        $session = new PhpSession();
        $this->testMiddlewareWithResponse($containerMock, $session, 'es-ES');
        $this->assertEquals('de', $session->get('language'));
    }
}
