<?php

declare(strict_types=1);

namespace Hexogen\Timesync;

/**
 * Provides the current server's IP address.
 */
interface ServerIPDetectorInterface
{
    /**
     * Returns the current server's public IP address.
     *
     * @return string The server's IP address
     */
    public function getCurrentServerIP(): string;
}
