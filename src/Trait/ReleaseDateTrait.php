<?php

declare(strict_types=1);

namespace App\Trait;

use DateTimeImmutable;
use DI\Container;

/**
 * Trait to calculate release dates
 */
trait ReleaseDateTrait
{
    /**
     * Get release date for given day
     * @param Container $container
     * @param int $day
     * @return DateTimeImmutable
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \DateMalformedStringException
     */
    protected function getReleaseDate(Container $container, int $day): DateTimeImmutable
    {
        $tz = $container->get('timezone');
        $now = new DateTimeImmutable('now', $tz);
        $year = $now->format('Y');
        $month = $container->get('adventMonth');
        if ($day < 1 || $day > 24) {
            $day = $now->format('d');
        }
        return new DateTimeImmutable("$year-$month-$day 00:00:00", $tz);
    }

    /**
     * Check if given day is released
     * @param Container $container
     * @param int $day
     * @return bool
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \DateMalformedStringException
     */
    protected function isReleased(Container $container, int $day): bool
    {
        $releaseDate = $this->getReleaseDate($container, $day);
        $now = new DateTimeImmutable('now', $container->get('timezone'));
        return $releaseDate <= $now;
    }
}
