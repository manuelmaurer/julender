<?php

declare(strict_types=1);

namespace App\Helper;

use DateInterval;
use DateTimeImmutable;
use DI\DependencyException;
use DI\NotFoundException;
use Psr\Container\ContainerInterface;

/**
 * Class to calculate release dates
 */
class ReleaseDate
{
    public const int RELEASE_DAY_START = 1;
    public const int RELEASE_DAY_END = 24;
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param string $referenceDate
     * @return array<int, array{ts: DateTimeImmutable, tsString: string, diff: DateInterval, diffString: string, isReleased: bool}>
     * @throws DependencyException
     * @throws NotFoundException
     * @throws \DateMalformedStringException
     */
    public function getAllReleaseDates(string $referenceDate = 'now'): array
    {
        $tz = $this->container->get('timezone');
        $now = new DateTimeImmutable($referenceDate, $tz);
        $year = $now->format('Y');
        $month = intval($this->container->get('adventMonth'));
        $dateFormat = $this->container->has('dateFormat') ? $this->container->get('dateFormat') : 'Y-m-d';
        return array_reduce(range(1, 24), function ($carry, $item) use ($now, $year, $month, $tz, $dateFormat) {
            $ts = new DateTimeImmutable("$year-$month-$item 00:00:00", $tz);
            $diff = $now->diff($ts);
            $carry[$item] = [
                'ts' => $ts,
                'tsString' => $ts->format($dateFormat),
                'diff' => $diff,
                'diffString' => $diff->format('%a'),
                'isReleased' => $ts <= $now,
            ];
            return $carry;
        }, []);
    }

    /**
     * Check if the given day is released
     * @param int $day
     * @param string $referenceDate
     * @return bool
     * @throws DependencyException
     * @throws NotFoundException
     * @throws \DateMalformedStringException
     */
    public function isReleased(int $day, string $referenceDate = 'now'): bool
    {
        return $this->getAllReleaseDates($referenceDate)[$day]['isReleased'] ?? false;
    }
}
