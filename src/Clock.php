<?php

declare(strict_types=1);

namespace Hexogen\Timesync;

use DateMalformedStringException;
use DateTimeImmutable;
use DateTimeZone;
use Psr\Clock\ClockInterface;

class Clock implements ClockInterface
{
    /**
     * The delta in seconds between the server's current time and the real time.
     */
    private float $delta;

    private string $timeZone;

    /**
     * @param float $realTimestamp The real timestamp in seconds (with microseconds) that we want to synchronize with.
     */
    public function __construct(float $realTimestamp, string $timeZone = 'UTC')
    {
        $serversTimestamp = microtime(true);

        $this->delta = $realTimestamp - $serversTimestamp;
        $this->timeZone = $timeZone;
    }

    /**
     * @throws DateMalformedStringException|\DateInvalidTimeZoneException
     */
    public function now(): DateTimeImmutable
    {
        $currentTimestamp = microtime(true) + $this->delta;

        $seconds = (int) floor($currentTimestamp);
        $microseconds = (int) round(($currentTimestamp - $seconds) * 1_000_000);

        $dateTime = (new DateTimeImmutable())->setTimestamp($seconds)->modify("+{$microseconds} microseconds");

        return $dateTime->setTimezone(new DateTimeZone($this->timeZone));
    }
}
