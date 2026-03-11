<?php

declare(strict_types=1);

namespace Hexogen\Timesync\Tests;

use Hexogen\Timesync\Clock;
use Hexogen\Timesync\GeolocationServiceException;
use Hexogen\Timesync\InvalidIpAddressException;
use Hexogen\Timesync\IPGeolocationClient;
use Hexogen\Timesync\ServerIPDetectorInterface;
use Hexogen\Timesync\SyncClientInterface;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;

class IPGeolocationClientTest extends TestCase
{
    private const API_KEY = 'test-api-key-123';
    private const TEST_IP = '37.17.245.123';

    private function getValidResponseJson(?float $unixTimestamp = null): string
    {
        if ($unixTimestamp === null) {
            $unixTimestamp = microtime(true);
        }

        return json_encode([
            'ip' => self::TEST_IP,
            'time_zone' => [
                'name' => 'UTC',
                'current_time_unix' => $unixTimestamp,
            ],
        ]);
    }

    private function createClient(
        string $apiKey,
        ClientInterface $httpClient,
        ?ServerIPDetectorInterface $ipDetector = null,
    ): IPGeolocationClient {
        if ($ipDetector === null) {
            $ipDetector = $this->createStub(ServerIPDetectorInterface::class);
            $ipDetector->method('getCurrentServerIP')->willReturn(self::TEST_IP);
        }

        return new IPGeolocationClient($apiKey, $httpClient, $ipDetector);
    }

    public function testImplementsSyncClientInterface(): void
    {
        // Arrange
        $httpClient = $this->createStub(ClientInterface::class);
        $client = $this->createClient(self::API_KEY, $httpClient);

        // Assert
        $this->assertInstanceOf(SyncClientInterface::class, $client);
    }

    /**
     * @throws ClientExceptionInterface
     */
    public function testGetCurrentTimeWithValidIpReturnsClockInterface(): void
    {
        // Arrange
        $httpClient = $this->createMock(ClientInterface::class);
        $httpClient->expects($this->once())
            ->method('sendRequest')
            ->willReturn(new Response(200, [], $this->getValidResponseJson()));

        $client = $this->createClient(self::API_KEY, $httpClient);

        // Act
        $clock = $client->getCurrentTime(self::TEST_IP);

        // Assert
        $this->assertInstanceOf(ClockInterface::class, $clock);
        $this->assertInstanceOf(Clock::class, $clock);
    }

    public function testGetCurrentTimeThrowsExceptionForInvalidIpAddress(): void
    {
        // Arrange
        $httpClient = $this->createMock(ClientInterface::class);
        $httpClient->expects($this->never())->method('sendRequest');

        $client = $this->createClient(self::API_KEY, $httpClient);

        // Assert
        $this->expectException(InvalidIpAddressException::class);
        $this->expectExceptionMessage('The provided IP address "invalid-ip" is not a valid IPv4 address.');

        // Act
        $client->getCurrentTime('invalid-ip');
    }

    public function testGetCurrentTimeThrowsExceptionForEmptyIpString(): void
    {
        // Arrange
        $httpClient = $this->createMock(ClientInterface::class);
        $httpClient->expects($this->never())->method('sendRequest');

        $client = $this->createClient(self::API_KEY, $httpClient);

        // Assert
        $this->expectException(InvalidIpAddressException::class);
        $this->expectExceptionMessage('The provided IP address "" is not a valid IPv4 address.');

        // Act
        $client->getCurrentTime('');
    }

    /**
     * @throws ClientExceptionInterface
     */
    public function testGetCurrentTimeThrowsExceptionForMalformedIp(): void
    {
        // Arrange
        $httpClient = $this->createMock(ClientInterface::class);
        $httpClient->expects($this->never())->method('sendRequest');

        $client = $this->createClient(self::API_KEY, $httpClient);

        // Assert
        $this->expectException(InvalidIpAddressException::class);
        $this->expectExceptionMessage('The provided IP address "999.999.999.999" is not a valid IPv4 address.');

        // Act
        $client->getCurrentTime('999.999.999.999');
    }

    public function testGetCurrentTimeThrowsExceptionForIPv6Address(): void
    {
        // Arrange
        $httpClient = $this->createMock(ClientInterface::class);
        $httpClient->expects($this->never())->method('sendRequest');

        $client = $this->createClient(self::API_KEY, $httpClient);

        // Assert
        $this->expectException(InvalidIpAddressException::class);
        $this->expectExceptionMessage('The provided IP address "2001:0db8:85a3:0000:0000:8a2e:0370:7334" is not a valid IPv4 address.');

        // Act
        $client->getCurrentTime('2001:0db8:85a3:0000:0000:8a2e:0370:7334');
    }

    public function testGetCurrentTimeBuildsCorrectRequestUrl(): void
    {
        // Arrange
        $expectedUrl = 'https://api.ipgeolocation.io/v3/ipgeo?apiKey=test-api-key-123&ip=37.17.245.123';

        $httpClient = $this->createMock(ClientInterface::class);
        $httpClient->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(function (RequestInterface $request) use ($expectedUrl) {
                return $request->getMethod() === 'GET'
                    && (string) $request->getUri() === $expectedUrl;
            }))
            ->willReturn(new Response(200, [], $this->getValidResponseJson()));

        $client = $this->createClient(self::API_KEY, $httpClient);

        // Act
        $client->getCurrentTime(self::TEST_IP);
    }

    public function testGetCurrentTimeThrowsExceptionFor500StatusCode(): void
    {
        // Arrange
        $httpClient = $this->createMock(ClientInterface::class);
        $httpClient->expects($this->once())
            ->method('sendRequest')
            ->willReturn(new Response(500, [], '{"error": "Internal Server Error"}'));

        $client = $this->createClient(self::API_KEY, $httpClient);

        // Assert
        $this->expectException(GeolocationServiceException::class);
        $this->expectExceptionMessage('Failed to retrieve geolocation data for IP "37.17.245.123". HTTP status code: 500');

        // Act
        $client->getCurrentTime(self::TEST_IP);
    }


    public function testGetCurrentTimeThrowsExceptionWhenCurrentTimeUnixFieldIsMissing(): void
    {
        // Arrange
        $responseJson = json_encode([
            'ip' => self::TEST_IP,
            'time_zone' => [
                'name' => 'Europe/Kyiv',
            ],
        ]);

        $httpClient = $this->createMock(ClientInterface::class);
        $httpClient->expects($this->once())
            ->method('sendRequest')
            ->willReturn(new Response(200, [], $responseJson));

        $client = $this->createClient(self::API_KEY, $httpClient);

        // Assert
        $this->expectException(GeolocationServiceException::class);
        $this->expectExceptionMessage('The response from the geolocation service does not contain the expected "current_time_unix" field for IP "37.17.245.123".');

        // Act
        $client->getCurrentTime(self::TEST_IP);
    }

    public function testGetCurrentTimeReturnsClockWithCorrectTimestamp(): void
    {
        // Arrange: Use a timestamp close to current time (5 seconds ahead)
        $expectedTimestamp = microtime(true) + 5.0;

        $httpClient = $this->createMock(ClientInterface::class);
        $httpClient->expects($this->once())
            ->method('sendRequest')
            ->willReturn(new Response(200, [], $this->getValidResponseJson($expectedTimestamp)));

        $client = $this->createClient(self::API_KEY, $httpClient);

        // Act
        $clock = $client->getCurrentTime(self::TEST_IP);
        $now = $clock->now();

        // Assert
        $clockTimestamp = (float) $now->format('U.u');
        $currentTime = microtime(true);
        $delta = $clockTimestamp - $currentTime;

        // Delta should be approximately 5 seconds (within 10ms)
        $this->assertGreaterThan(4.99, $delta, 'Clock should be about 5 seconds ahead');
        $this->assertLessThan(5.01, $delta, 'Clock should be about 5 seconds ahead');
    }

    public function testGetCurrentTimePropagatesHttpClientException(): void
    {
        // Arrange
        $httpClient = $this->createMock(ClientInterface::class);
        $exception = new class extends \Exception implements ClientExceptionInterface {};

        $httpClient->expects($this->once())
            ->method('sendRequest')
            ->willThrowException($exception);

        $client = $this->createClient(self::API_KEY, $httpClient);

        // Assert
        $this->expectException(ClientExceptionInterface::class);

        // Act
        $client->getCurrentTime(self::TEST_IP);
    }

    public function testGetCurrentTimeWithCompleteApiResponse(): void
    {
        // Arrange
        $fixtureContent = file_get_contents(__DIR__ . '/fixtures/ip_geolocation_response.json');

        $httpClient = $this->createMock(ClientInterface::class);
        $httpClient->expects($this->once())
            ->method('sendRequest')
            ->willReturn(new Response(200, [], $fixtureContent));

        $client = $this->createClient(self::API_KEY, $httpClient);

        // Act
        $clock = $client->getCurrentTime(self::TEST_IP);

        // Assert
        $this->assertInstanceOf(ClockInterface::class, $clock);
        $this->assertInstanceOf(Clock::class, $clock);

        $now = $clock->now();
        $this->assertInstanceOf(\DateTimeImmutable::class, $now);
    }

    public function testGetCurrentTimeThrowsExceptionForInvalidJsonResponse(): void
    {
        // Arrange
        $httpClient = $this->createMock(ClientInterface::class);
        $httpClient->expects($this->once())
            ->method('sendRequest')
            ->willReturn(new Response(200, [], 'invalid json {{{'));

        $client = $this->createClient(self::API_KEY, $httpClient);

        // Assert
        $this->expectException(GeolocationServiceException::class);
        $this->expectExceptionMessage('The response from the geolocation service does not contain the expected "current_time_unix" field');

        // Act
        $client->getCurrentTime(self::TEST_IP);
    }

    public function testGetCurrentTimeHandlesTimestampWithMicrosecondPrecision(): void
    {
        // Arrange
        $timestampWithMicroseconds = 1773073817.123456;

        $httpClient = $this->createMock(ClientInterface::class);
        $httpClient->expects($this->once())
            ->method('sendRequest')
            ->willReturn(new Response(200, [], $this->getValidResponseJson($timestampWithMicroseconds)));

        $client = $this->createClient(self::API_KEY, $httpClient);

        // Act
        $clock = $client->getCurrentTime(self::TEST_IP);
        $now = $clock->now();

        // Assert
        $this->assertInstanceOf(ClockInterface::class, $clock);

        // Verify the clock has microsecond precision
        $formatted = $now->format('u');
        $this->assertIsNumeric($formatted);
        $this->assertGreaterThanOrEqual(0, (int) $formatted);
        $this->assertLessThanOrEqual(999999, (int) $formatted);
    }


    public function testGetCurrentTimeHandlesIntegerTimestamp(): void
    {
        // Arrange: Response with integer timestamp (no microseconds)
        $integerTimestamp = 1773073817.0;

        $httpClient = $this->createMock(ClientInterface::class);
        $httpClient->expects($this->once())
            ->method('sendRequest')
            ->willReturn(new Response(200, [], $this->getValidResponseJson($integerTimestamp)));

        $client = $this->createClient(self::API_KEY, $httpClient);

        // Act
        $clock = $client->getCurrentTime(self::TEST_IP);

        // Assert
        $this->assertInstanceOf(ClockInterface::class, $clock);
        $now = $clock->now();
        $this->assertInstanceOf(\DateTimeImmutable::class, $now);
    }

    public function testGetCurrentTimeCreatesNewClockInstanceEachTime(): void
    {
        // Arrange
        $timestamp = microtime(true) + 5.0;

        $httpClient = $this->createMock(ClientInterface::class);
        $httpClient->expects($this->exactly(2))
            ->method('sendRequest')
            ->willReturnOnConsecutiveCalls(
                new Response(200, [], $this->getValidResponseJson($timestamp)),
                new Response(200, [], $this->getValidResponseJson($timestamp)),
            );

        $client = $this->createClient(self::API_KEY, $httpClient);

        // Act
        $clock1 = $client->getCurrentTime(self::TEST_IP);
        $clock2 = $client->getCurrentTime(self::TEST_IP);

        // Assert
        $this->assertNotSame($clock1, $clock2, 'Each call should create a new Clock instance');
    }

    public function testGetCurrentTimeWithDifferentApiKeys(): void
    {
        // Arrange
        $apiKeys = [
            'key1',
            'another-key-123',
            'UPPERCASE_KEY',
        ];

        foreach ($apiKeys as $apiKey) {
            $httpClient = $this->createMock(ClientInterface::class);
            $httpClient->expects($this->once())
                ->method('sendRequest')
                ->with($this->callback(function (RequestInterface $request) use ($apiKey) {
                    $uri = (string) $request->getUri();

                    return str_contains($uri, "apiKey=$apiKey");
                }))
                ->willReturn(new Response(200, [], $this->getValidResponseJson()));

            $client = $this->createClient($apiKey, $httpClient);

            // Act
            $clock = $client->getCurrentTime(self::TEST_IP);

            // Assert
            $this->assertInstanceOf(ClockInterface::class, $clock, "Failed for API key: $apiKey");
        }
    }


    public function testGetCurrentTimeHandlesVeryLargeTimestamp(): void
    {
        // Arrange: Far future timestamp
        // 32 bit overflow point + 1 second
        $futureTimestamp = 2147483648.0;

        $httpClient = $this->createMock(ClientInterface::class);
        $httpClient->expects($this->once())
            ->method('sendRequest')
            ->willReturn(new Response(200, [], $this->getValidResponseJson($futureTimestamp)));

        $client = $this->createClient(self::API_KEY, $httpClient);

        // Act
        $clock = $client->getCurrentTime(self::TEST_IP);

        // Assert
        $this->assertInstanceOf(ClockInterface::class, $clock);
        $now = $clock->now();
        $this->assertInstanceOf(\DateTimeImmutable::class, $now);
        $this->assertGreaterThanOrEqual(new \DateTimeImmutable('2038-01-19T03:14:08Z'), $now);
    }

    public static function validIpAddressesProvider(): array
    {
        return [
            'edge_1_0_0_0' => ['1.0.0.0'],
            'edge_255_0_0_0' => ['255.0.0.0'],
            'edge_0_255_0_0' => ['0.255.0.0'],
            'edge_0_0_255_0' => ['0.0.255.0'],
            'edge_0_0_0_255' => ['0.0.0.255'],
            'edge_0_0_0_1' => ['0.0.0.1'],
            'google_dns' => ['8.8.8.8'],
            'cloudflare_dns' => ['1.1.1.1'],
            'opendns' => ['208.67.222.222'],
            'all_zeros' => ['0.0.0.0'],
            'private_a' => ['10.0.0.1'],
            'private_b' => ['172.16.0.1'],
        ];
    }

    #[DataProvider('validIpAddressesProvider')]
    public function testGetCurrentTimeWithDifferentPublicIpAddresses(string $ip): void
    {
        // Arrange
        $httpClient = $this->createMock(ClientInterface::class);
        $httpClient->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(function (RequestInterface $request) use ($ip) {
                $uri = (string) $request->getUri();

                return str_contains($uri, "ip=$ip");
            }))
            ->willReturn(new Response(200, [], $this->getValidResponseJson()));

        $client = $this->createClient(self::API_KEY, $httpClient);

        // Act
        $clock = $client->getCurrentTime($ip);

        // Assert
        $this->assertInstanceOf(ClockInterface::class, $clock, "Failed for IP: $ip");
    }
}
