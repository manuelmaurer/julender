<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Controller\ImageController;
use App\Helper\ReleaseDate;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpUnauthorizedException;

#[CoversClass(ImageController::class)]
class ImageControllerTest extends TestCase
{
    private string $mediaPath = __DIR__ . '/../assets/media';
    /**
     * @return array<string, array<int>>
     */
    public static function invalidDayDataProvider(): array
    {
        return [
            'negative' => [-1],
            'zero' => [0],
            'positive' => [25],
        ];
    }

    /**
     * @return array<string, array<int>>
     */
    public static function invalidFileDataProvider(): array
    {
        return [
            'missing file' => [05],
            'empty file' => [04],
        ];
    }

    /**
     * @return array<string, array<int|string>>
     */
    public static function validFileDataProvider(): array
    {
        return [
            'png' => [01, 'image/png', 1233],
            'jpg' => [02, 'image/gif', 1282],
            'gif' => [03, 'image/jpeg', 928],
            'binary' => [06, 'application/octet-stream', 155],
        ];
    }

    /**
     * @param int $day
     * @return void
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \DateMalformedStringException
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    #[DataProvider('invalidDayDataProvider')]
    public function testInvalidDays(int $day): void
    {
        $requestMock = $this->createMock(Request::class);
        $responseMock = $this->createMock(Response::class);
        $rdMock = $this->createMock(ReleaseDate::class);
        $dut = new ImageController();
        $this->expectException(HttpNotFoundException::class);
        $this->expectExceptionMessage('Invalid day');
        $dut->get($requestMock, $responseMock, $rdMock, strval($day));
    }

    /**
     * @return void
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \DateMalformedStringException
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testUnreleased(): void
    {
        $requestMock = $this->createMock(Request::class);
        $responseMock = $this->createMock(Response::class);
        $rdMock = $this->getMockBuilder(ReleaseDate::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isReleased'])
            ->getMock();
        $rdMock
            ->expects($this->once())
            ->method('isReleased')
            ->with($this->equalTo(11))
            ->willReturn(false);
        $dut = new ImageController();
        $this->expectException(HttpUnauthorizedException::class);
        $this->expectExceptionMessage('Not yet released');
        $dut->get($requestMock, $responseMock, $rdMock, '11');
    }

    /**
     * @param int $day
     * @return void
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \DateMalformedStringException
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    #[DataProvider('invalidFileDataProvider')]
    public function testFileNotFound(int $day): void
    {
        $requestMock = $this->createMock(Request::class);
        $responseMock = $this->createMock(Response::class);
        $rdMock = $this->getMockBuilder(ReleaseDate::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isReleased'])
            ->getMock();
        $rdMock
            ->expects($this->once())
            ->method('isReleased')
            ->with($this->equalTo($day))
            ->willReturn(true);
        $dut = new ImageController($this->mediaPath);
        $this->expectException(HttpNotFoundException::class);
        $this->expectExceptionMessage('File not found');
        $dut->get($requestMock, $responseMock, $rdMock, strval($day));
    }

    /**
     * @param int $day
     * @param string $expectedMime
     * @param int $expectedSize
     * @return void
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \DateMalformedStringException
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    #[DataProvider('validFileDataProvider')]
    public function testValidFiles(int $day, string $expectedMime, int $expectedSize): void
    {
        $requestMock = $this->createMock(Request::class);
        $response = new \Slim\Psr7\Response();
        $rdMock = $this->getMockBuilder(ReleaseDate::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isReleased'])
            ->getMock();
        $rdMock
            ->expects($this->once())
            ->method('isReleased')
            ->with($this->equalTo($day))
            ->willReturn(true);
        $dut = new ImageController($this->mediaPath);
        $result = $dut->get($requestMock, $response, $rdMock, strval($day));
        $this->assertEquals($expectedMime, $result->getHeaderLine('Content-Type'));
        $this->assertEquals($expectedSize, $result->getHeaderLine('Content-Length'));
    }
}
