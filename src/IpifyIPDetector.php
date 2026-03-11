<?php

declare(strict_types=1);

namespace Hexogen\Timesync;

use Nyholm\Psr7\Request;
use Psr\Http\Client\ClientInterface;

class IpifyIPDetector implements ServerIPDetectorInterface
{
    private const string SERVICE_URL = 'https://api.ipify.org?format=json';

    public function __construct(private readonly ClientInterface $httpClient) {}

    public function getCurrentServerIP(): string
    {
        $response = $this->httpClient->sendRequest(
            new Request('GET', self::SERVICE_URL),
        );

        if ($response->getStatusCode() !== 200) {
            throw new ServerIpDetectionException(
                sprintf(
                    'Failed to retrieve server IP address. HTTP status code: %d',
                    $response->getStatusCode(),
                ),
            );
        }

        $data = json_decode($response->getBody()->getContents(), true);

        if (!isset($data['ip'])) {
            throw new ServerIpDetectionException(
                'The response from the IP service does not contain the expected "ip" field.',
            );
        }

        return $data['ip'];
    }
}
