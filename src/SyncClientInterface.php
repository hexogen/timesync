<?php

declare(strict_types=1);

namespace Hexogen\Timesync;

use Psr\Clock\ClockInterface;
use Psr\Http\Client\ClientExceptionInterface;

interface SyncClientInterface
{
    /**
     * Returns a clock synchronized with the time for the given IP address.
     *
     * @throws InvalidIpAddressException When the provided IP is not a valid IPv4 address.
     * @throws GeolocationServiceException When the external geolocation service responds with
     *                                     a non-success status code or an unexpected payload.
     * @throws ClientExceptionInterface When the underlying HTTP client fails to execute
     *                                  the request.
     */
    public function getCurrentTime(string $ip): ClockInterface;
}
