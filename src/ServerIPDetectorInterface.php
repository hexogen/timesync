<?php

declare(strict_types=1);

namespace Hexogen\Timesync;

use Psr\Http\Client\ClientExceptionInterface;

/**
 * Provides the current server's IP address.
 */
interface ServerIPDetectorInterface
{
    /**
     * Returns the current server's public IP address.
     *
     * @return string The server's IP address
     *
     * @throws ServerIpDetectionException When the IP service responds with a non-success
     *                                    status code or an unexpected payload.
     * @throws ClientExceptionInterface When the underlying HTTP client fails to execute
     *                                  the request.
     */
    public function getCurrentServerIP(): string;
}
