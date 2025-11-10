<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Controller\AuthenticationController;
use DI\Container;
use Odan\Session\PhpSession;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Slim\Views\Twig;
use Psr\Http\Message\ResponseInterface as Response;

#[CoversClass(AuthenticationController::class)]
class AuthenticationControllerTest extends TestCase
{
    /**
     * @return array<string, array{0: mixed}>
     */
    public static function invalidPasswordDataProvider(): array
    {
        return [
            'empty string' => [''],
            'invalid string' => ['invalid'],
            'null' => [null],
            'empty array' => [[],],
            'array: wrong key' => [['wrong' => 'phpunit'],],
            'array: wrong password' => [['password' => 'invalid'],],
            'object: wrong key' => [(object) ['wrong' => 'phpunit']],
            'object: wrong password' => [(object) ['password' => 'invalid']],
        ];
    }

    /**
     * @return void
     * @throws \PHPUnit\Framework\MockObject\Exception
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function testLoginGet(): void
    {
        $responseMock = $this->createMock(Response::class);
        $twigMock = $this->getMockBuilder(Twig::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['render'])
            ->getMock();
        $twigMock
            ->expects($this->once())
            ->method('render')
            ->with($this->identicalTo($responseMock), $this->equalTo('login.twig'))
            ->willReturn($responseMock);
        $dut = new AuthenticationController();
        $result = $dut->getLogin($responseMock, $twigMock);
        $this->assertEquals($result, $responseMock);
    }

    /**
     * @return void
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testLoginPostPasswordNotSet(): void
    {
        $response = new \Slim\Psr7\Response();
        $requestMock = $this->createMock(\Slim\Psr7\Request::class);
        $session = new PhpSession();
        $containerMock = $this->getMockBuilder(Container::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['has'])
            ->getMock();
        $containerMock
            ->expects($this->once())
            ->method('has')
            ->with($this->equalTo('password'))
            ->willReturn(false);
        $dut = new AuthenticationController();
        // Request $request, Response $response, PhpSession $session, Container $container
        $result = $dut->postLogin($requestMock, $response, $session, $containerMock);
        $this->assertEquals(302, $result->getStatusCode());
        $this->assertEquals('/login', $result->getHeaderLine('Location'));
        $flash = $session->getFlash();
        $this->assertTrue($flash->has('login-error'));
        $this->assertEquals('Server error', $flash->get('login-error')[0] ?? null);
    }

    /**
     * @param mixed $payload
     * @return void
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     */
    #[DataProvider('invalidPasswordDataProvider')]
    public function testLoginPostPasswordInvalid(mixed $payload): void
    {
        $response = new \Slim\Psr7\Response();
        $requestMock = $this->getMockBuilder(\Slim\Psr7\Request::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getParsedBody'])
            ->getMock();
        $requestMock
            ->expects($this->once())
            ->method('getParsedBody')
            ->willReturn($payload);
        $session = new PhpSession();
        $containerMock = $this->getMockBuilder(Container::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['has', 'get'])
            ->getMock();
        $containerMock
            ->expects($this->once())
            ->method('has')
            ->with($this->equalTo('password'))
            ->willReturn(true);
        $containerMock
            ->expects($this->once())
            ->method('get')
            ->with($this->equalTo('password'))
            ->willReturn('phpunit');
        $dut = new AuthenticationController();
        // Request $request, Response $response, PhpSession $session, Container $container
        $result = $dut->postLogin($requestMock, $response, $session, $containerMock);
        $this->assertEquals(302, $result->getStatusCode());
        $this->assertEquals('/login', $result->getHeaderLine('Location'));
        $flash = $session->getFlash();
        $this->assertTrue($flash->has('login-error'));
        $this->assertEquals('Invalid password', $flash->get('login-error')[0] ?? null);
    }

    /**
     * @return void
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     */
    public function testLoginPostPasswordValid(): void
    {
        $response = new \Slim\Psr7\Response();
        $requestMock = $this->getMockBuilder(\Slim\Psr7\Request::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getParsedBody'])
            ->getMock();
        $requestMock
            ->expects($this->once())
            ->method('getParsedBody')
            ->willReturn(['password' => 'phpunit']);
        $session = new PhpSession();
        $containerMock = $this->getMockBuilder(Container::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['has', 'get'])
            ->getMock();
        $containerMock
            ->expects($this->once())
            ->method('has')
            ->with($this->equalTo('password'))
            ->willReturn(true);
        $containerMock
            ->expects($this->once())
            ->method('get')
            ->with($this->equalTo('password'))
            ->willReturn('phpunit');
        $dut = new AuthenticationController();
        // Request $request, Response $response, PhpSession $session, Container $container
        $result = $dut->postLogin($requestMock, $response, $session, $containerMock);
        $this->assertEquals(302, $result->getStatusCode());
        $this->assertEquals('/', $result->getHeaderLine('Location'));
        $this->assertTrue($session->has('loggedIn'));
        $this->assertTrue($session->get('loggedIn'));
    }
}
