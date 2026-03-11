<?php

declare(strict_types=1);

namespace Hexogen\Timesync;

use Psr\Clock\ClockInterface;

readonly class SyncService implements SyncServiceInterface
{
    public function __construct(
        private SyncClientInterface       $syncClient,
        private ServerIPDetectorInterface $serverIPDetector,
    ) {}

    /**
     * @param string|null $ip The IP address to synchronize with. If null, the server's current IP will be used.
     *
     * @return ClockInterface A clock synchronized with the time from the specified IP address.
     */
    public function getCurrentTime(?string $ip = null): ClockInterface
    {
        if (null === $ip) {
            $ip = $this->serverIPDetector->getCurrentServerIP();
        }

        return $this->syncClient->getCurrentTime($ip);
    }
}
