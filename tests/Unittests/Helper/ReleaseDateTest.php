<?php

declare(strict_types=1);

namespace App\Tests\Unittests\Helper;

use App\Helper\ReleaseDate;
use DateInterval;
use DateTimeImmutable;
use DateTimeZone;
use DI\Container;
use DI\DependencyException;
use DI\NotFoundException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(ReleaseDate::class)]
final class ReleaseDateTest extends TestCase
{
    /**
     * @return array<string, array<int>>
     */
    public static function releaseDateDataProvider(): array
    {
        $range = range(ReleaseDate::RELEASE_DAY_START, ReleaseDate::RELEASE_DAY_END);
        return array_reduce($range, function ($carry, $day) {
            $carry["Day $day"] = [$day];
            return $carry;
        }, []);
    }

    /**
     * @return array<string, array{string, bool}>
     */
    public static function outsideReleaseDataProvider(): array
    {
        return [
            'early' => ['2025-11-01 12:00:00', false],
            'late' => ['2025-12-25 12:00:00', true],
        ];
    }

    /**
     * @return array<string, array{string, int, bool}>
     */
    public static function isReleasedDataProvider(): array
    {
        return [
            'before' => ['2025-11-01 12:00:00', 1, false],
            'on' => ['2025-12-01 12:00:00', 1, true],
            'after' => ['2025-12-02 12:00:00', 1, true],
            'before last' => ['2025-12-23 12:00:00', 24, false],
            'on last' => ['2025-12-24 12:00:00', 24, true],
            'after last' => ['2025-12-25 12:00:00', 24, true],
        ];
    }

    /**
     * @return Container
     */
    private function getContainerMock(): Container
    {
        $containerMock = $this->getMockBuilder(\DI\Container::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['get', 'has'])
            ->getMock();
        $invokeAssertion = $this->exactly(2);
        $containerMock
            ->expects($invokeAssertion)
            ->method('get')
            ->willReturnCallback(function ($parameter) use ($invokeAssertion) {
                $tz = new DateTimeZone('Europe/Berlin');
                if ($invokeAssertion->numberOfInvocations() === intval(1)) {
                    $this->assertEquals('timezone', $parameter);
                    return $tz;
                } elseif ($invokeAssertion->numberOfInvocations() === intval(2)) {
                    $this->assertEquals('adventMonth', $parameter);
                    return intval(12);
                }
                return null;
            });

        return $containerMock;
    }

    /**
     * @param array<int, array{ts: DateTimeImmutable, diff: DateInterval, diffDays: string, isReleased: bool}> $data
     * @return void
     */
    private function validateBasicArray(array $data): void
    {
        $this->assertCount(24, $data);
        for ($day = ReleaseDate::RELEASE_DAY_START; $day <= ReleaseDate::RELEASE_DAY_END; $day++) {
            $this->assertArrayHasKey($day, $data);
            $this->assertArrayHasKey('ts', $data[$day]);
            $this->assertInstanceOf(\DateTimeImmutable::class, $data[$day]['ts']);
            $this->assertArrayHasKey('diff', $data[$day]);
            $this->assertInstanceOf(\DateInterval::class, $data[$day]['diff']);
            $this->assertArrayHasKey('diffDays', $data[$day]);
            $this->assertIsString($data[$day]['diffDays']);
            $this->assertArrayHasKey('isReleased', $data[$day]);
            $this->assertIsBool($data[$day]['isReleased']);
        }
    }

    /**
     * @param int $day
     * @return void
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \DateMalformedStringException
     */
    #[DataProvider('releaseDateDataProvider')]
    public function testReleaseDates(int $day): void
    {
        $containerMock = $this->getContainerMock();
        $dut = new ReleaseDate($containerMock);
        $data = $dut->getAllReleaseDates(sprintf("2025-12-%02d 12:00:00", $day));
        $this->validateBasicArray($data);
    }

    /**
     * @param string $ts
     * @param bool $expectedResult
     * @return void
     * @throws DependencyException
     * @throws NotFoundException
     * @throws \DateMalformedStringException
     */
    #[DataProvider('outsideReleaseDataProvider')]
    public function testOutsideReleaseDates(string $ts, bool $expectedResult): void
    {
        $containerMock = $this->getContainerMock();
        $dut = new ReleaseDate($containerMock);
        $data = $dut->getAllReleaseDates($ts);
        $this->validateBasicArray($data);
        for ($day = ReleaseDate::RELEASE_DAY_START; $day <= ReleaseDate::RELEASE_DAY_END; $day++) {
            $this->assertEquals($expectedResult, $data[$day]['isReleased']);
        }
    }

    /**
     * @param string $referenceDate
     * @param int $day
     * @param bool $expectedResult
     * @return void
     * @throws DependencyException
     * @throws NotFoundException
     * @throws \DateMalformedStringException
     */
    #[DataProvider('isReleasedDataProvider')]
    public function testIsReleased(string $referenceDate, int $day, bool $expectedResult): void
    {
        $containerMock = $this->getContainerMock();
        $dut = new ReleaseDate($containerMock);
        $this->assertEquals($expectedResult, $dut->isReleased($day, $referenceDate));
    }
}
