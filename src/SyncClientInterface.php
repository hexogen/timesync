<?php

declare(strict_types=1);

namespace Hexogen\Timesync;

use Psr\Clock\ClockInterface;

interface SyncClientInterface
{
    public function getCurrentTime(?string $ip = null): ClockInterface;
}
