<?php

declare(strict_types=1);

namespace Hexogen\Timesync;

use Psr\Clock\ClockInterface;
use Psr\Http\Client\ClientExceptionInterface;

interface SyncServiceInterface
{
    /**
     * Returns a clock synchronized with the time for the given IP address or,
     * when no IP is provided, for the current server IP.
     *
     * @throws InvalidIpAddressException When the provided IP is not a valid IPv4 address.
     * @throws GeolocationServiceException When the external geolocation service responds with
     *                                     a non-success status code or an unexpected payload.
     * @throws ServerIpDetectionException When the server IP cannot be determined.
     * @throws ClientExceptionInterface When the underlying HTTP client fails to execute
     *                                  any of the required requests.
     */
    public function getCurrentTime(?string $ip = null): ClockInterface;
}
