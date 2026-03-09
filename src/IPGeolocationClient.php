<?php

declare(strict_types=1);

namespace Hexogen\Timesync;

use Nyholm\Psr7\Request;
use Psr\Clock\ClockInterface;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;

class IPGeolocationClient implements SyncClientInterface
{
    private const string SERVICE_URL = 'https://api.ipgeolocation.io/v3/ipgeo';

    public function __construct(
        private readonly string $apiKey,
        private readonly ClientInterface $httpClient,
        private readonly ServerIPDetectorInterface $ipDetector,
    ) {}

    /**
     * @throws ClientExceptionInterface
     */
    public function getCurrentTime(?string $ip = null): ClockInterface
    {
        if (null === $ip) {
            $ip = $this->ipDetector->getCurrentServerIP();
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false) {
            throw new \InvalidArgumentException(sprintf(
                'The provided IP address "%s" is not a valid IPv4 address.',
                $ip,
            ));
        }

        $query = http_build_query([
            'apiKey' => $this->apiKey,
            'ip' => $ip,
        ], '', '&', PHP_QUERY_RFC3986);

        $response = $this->httpClient->sendRequest(
            new Request('GET', self::SERVICE_URL . '?' . $query),
        );

        if ($response->getStatusCode() !== 200) {
            throw new \RuntimeException(
                sprintf(
                    'Failed to retrieve geolocation data for IP "%s". HTTP status code: %d',
                    $ip,
                    $response->getStatusCode(),
                ),
            );
        }

        $data = json_decode($response->getBody()->getContents(), true);

        if (!isset($data['time_zone']['current_time_unix'])) {
            throw new \RuntimeException(
                sprintf('The response from the geolocation service does not contain the expected '
                    . '"current_time_unix" field for IP "%s".', $ip),
            );
        }

        return new Clock($data['time_zone']['current_time_unix'], $data['time_zone']['name']);
    }
}
