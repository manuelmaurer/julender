<?php

declare(strict_types=1);

namespace App\Tests\Unittests\Middleware;

use App\Middleware\ApiKeyAuthMiddleware;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpUnauthorizedException;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

#[CoversClass(ApiKeyAuthMiddleware::class)]
final class ApiKeyAuthMiddlewareTest extends TestCase
{
    /**
     * @return void
     */
    public function testEmptyApiKey(): void
    {
        $dut = new ApiKeyAuthMiddleware('');
        $this->expectException(HttpUnauthorizedException::class);
        $this->expectExceptionMessage('Unauthorized');
        $dut(
            $this->createMock(Request::class),
            $this->getMockBuilder(RequestHandler::class)->disableOriginalConstructor()->getMock(),
        );
    }

    /**
     * @return void
     */
    public function testInvalidApiKey(): void
    {
        $requestMock = $this->getMockBuilder(\Slim\Psr7\Request::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getHeaderLine'])
            ->getMock();
        $requestMock
            ->expects($this->once())
            ->method('getHeaderLine')
            ->with('X-API-KEY')
            ->willReturn('invalid');
        $dut = new ApiKeyAuthMiddleware('phpunit');
        $this->expectException(HttpUnauthorizedException::class);
        $this->expectExceptionMessage('Unauthorized');
        $dut(
            $requestMock,
            $this->getMockBuilder(RequestHandler::class)->disableOriginalConstructor()->getMock(),
        );
    }

    /**
     * @return void
     */
    public function testValidApiKey(): void
    {
        $responseMock = $this->getMockBuilder(Response::class)->getMock();
        $requestMock = $this->getMockBuilder(\Slim\Psr7\Request::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getHeaderLine'])
            ->getMock();
        $requestMock
            ->expects($this->once())
            ->method('getHeaderLine')
            ->with('X-API-KEY')
            ->willReturn('phpunit');

        $handlerMock = $this->getMockBuilder(RequestHandler::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['handle'])
            ->getMock();
        $handlerMock
            ->expects($this->once())
            ->method('handle')
            ->with($this->equalTo($requestMock))
            ->willReturn($responseMock);
        $dut = new ApiKeyAuthMiddleware('phpunit');
        $response = $dut($requestMock, $handlerMock);
        $this->assertEquals($response, $responseMock);
    }
}
