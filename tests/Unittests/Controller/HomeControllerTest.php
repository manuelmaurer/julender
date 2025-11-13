<?php

declare(strict_types=1);

namespace App\Tests\Unittests\Controller;

use App\Controller\HomeController;
use App\Helper\ReleaseDate;
use DI\Container;
use Odan\Session\MemorySession;
use Odan\Session\SessionInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Exception\HttpInternalServerErrorException;
use Slim\Exception\HttpNotFoundException;
use Slim\Views\Twig;

#[CoversClass(HomeController::class)]
class HomeControllerTest extends TestCase
{
    /**
     * @return array<string, mixed>
     */
    public static function invalidLanguagesDataProvider(): array
    {
        return [
            'empty array' => [[]],
            'no array' => [(object) ['test' => 'value']],
        ];
    }

    /**
     * @return void
     * @throws \PHPUnit\Framework\MockObject\Exception
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function testHome(): void
    {
        $rdMockData = [
            'test' => 'data',
        ];
        $responseMock = $this->createMock(Response::class);
        $rdMock = $this->getMockBuilder(ReleaseDate::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAllReleaseDates'])
            ->getMock();
        $rdMock
            ->expects($this->once())
            ->method('getAllReleaseDates')
            ->willReturn($rdMockData);
        $twigMock = $this->getMockBuilder(Twig::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['render'])
            ->getMock();
        $twigMock
            ->expects($this->once())
            ->method('render')
            ->with($this->identicalTo($responseMock), $this->equalTo('home.twig'))
            ->willReturn($responseMock);
        $dut = new HomeController();
        $result = $dut->home($responseMock, $twigMock, $rdMock);
        $this->assertEquals($result, $responseMock);
    }

    /**
     * @return void
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testLanguageMissingConfig(): void
    {
        $responseMock = $this->createMock(Response::class);
        $requestMock = $this->createMock(Request::class);
        $containerMock = $this->getMockBuilder(Container::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['has'])
            ->getMock();
        $containerMock
            ->expects($this->once())
            ->method('has')
            ->with($this->equalTo('languages'))
            ->willReturn(false);
        $sessionMock = $this->createMock(SessionInterface::class);
        $dut = new HomeController();
        $this->expectException(HttpInternalServerErrorException::class);
        $this->expectExceptionMessage('Invalid configuration');
        $dut->language($requestMock, $responseMock, $containerMock, $sessionMock, 'de');
    }

    /**
     * @param mixed $payload
     * @return void
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    #[DataProvider('invalidLanguagesDataProvider')]
    public function testLanguageInvalidConfig(mixed $payload): void
    {
        $responseMock = $this->createMock(Response::class);
        $requestMock = $this->createMock(Request::class);
        $containerMock = $this->getMockBuilder(Container::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['has', 'get'])
            ->getMock();
        $containerMock
            ->expects($this->once())
            ->method('has')
            ->with($this->equalTo('languages'))
            ->willReturn(true);
        $containerMock
            ->expects($this->once())
            ->method('get')
            ->with($this->equalTo('languages'))
            ->willReturn($payload);
        $sessionMock = $this->createMock(SessionInterface::class);
        $dut = new HomeController();
        $this->expectException(HttpInternalServerErrorException::class);
        $this->expectExceptionMessage('Invalid configuration');
        $dut->language($requestMock, $responseMock, $containerMock, $sessionMock, 'de');
    }

    /**
     * @return void
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testLanguageInvalidLanguage(): void
    {
        $responseMock = $this->createMock(Response::class);
        $requestMock = $this->createMock(Request::class);
        $containerMock = $this->getMockBuilder(Container::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['has', 'get'])
            ->getMock();
        $containerMock
            ->expects($this->once())
            ->method('has')
            ->with($this->equalTo('languages'))
            ->willReturn(true);
        $containerMock
            ->expects($this->once())
            ->method('get')
            ->with($this->equalTo('languages'))
            ->willReturn(['de', 'en']);
        $sessionMock = $this->createMock(SessionInterface::class);
        $dut = new HomeController();
        $this->expectException(HttpNotFoundException::class);
        $this->expectExceptionMessage('Invalid language');
        $dut->language($requestMock, $responseMock, $containerMock, $sessionMock, 'fr');
    }

    /**
     * @return void
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testValidLanguage(): void
    {
        $response = new \Slim\Psr7\Response();
        $requestMock = $this->createMock(Request::class);
        $containerMock = $this->getMockBuilder(Container::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['has', 'get'])
            ->getMock();
        $containerMock
            ->expects($this->once())
            ->method('has')
            ->with($this->equalTo('languages'))
            ->willReturn(true);
        $containerMock
            ->expects($this->once())
            ->method('get')
            ->with($this->equalTo('languages'))
            ->willReturn(['de', 'en']);
        $session = new MemorySession();
        $dut = new HomeController();
        $result = $dut->language($requestMock, $response, $containerMock, $session, 'de');
        $this->assertEquals(302, $result->getStatusCode());
        $this->assertEquals('/', $result->getHeaderLine('Location'));
        $this->assertEquals('de', $session->get('language'));
    }
}
