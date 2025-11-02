<?php

namespace App\Trait;

use DateTimeImmutable;

trait ReleaseDateTrait
{
    public function getReleaseDate(int $day, \DateTimeZone $tz): DateTimeImmutable
    {
        $year = new DateTimeImmutable('now', $tz)->format('Y');
        $month = intval($_ENV['ADVENT_MONTH'] ?? 12);
        return new DateTimeImmutable("$year-$month-$day 00:00:00", $tz);
    }
}
