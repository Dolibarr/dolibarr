<?php

declare(strict_types=1);

namespace ZipStream;

use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;
use ZipStream\Exception\DosTimeOverflowException;

/**
 * @internal
 */
abstract class Time
{
    private const DOS_MINIMUM_DATE = '1980-01-01 00:00:00Z';

    public static function dateTimeToDosTime(DateTimeInterface $dateTime): int
    {
        $dosMinimumDate = new DateTimeImmutable(self::DOS_MINIMUM_DATE);

        if ($dateTime->getTimestamp() < $dosMinimumDate->getTimestamp()) {
            throw new DosTimeOverflowException(dateTime: $dateTime);
        }

        $dateTime = DateTimeImmutable::createFromInterface($dateTime)->sub(new DateInterval('P1980Y'));

        [$year, $month, $day, $hour, $minute, $second] = explode(' ', $dateTime->format('Y n j G i s'));

        return
            ((int) $year << 25) |
            ((int) $month << 21) |
            ((int) $day << 16) |
            ((int) $hour << 11) |
            ((int) $minute << 5) |
            ((int) $second >> 1);
    }
}
