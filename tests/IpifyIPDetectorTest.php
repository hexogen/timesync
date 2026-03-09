<?php

declare(strict_types=1);

namespace Hexogen\Timesync\Tests;

use Hexogen\Timesync\IpifyIPDetector;
use Hexogen\Timesync\ServerIPDetectorInterface;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;

class IpifyIPDetectorTest extends TestCase
{
    private const SERVICE_URL = 'https://api.ipify.org?format=json';
    private const VALID_IP = '37.17.245.123';

    private function getValidResponseJson(string $ip = self::VALID_IP): string
    {
        return json_encode(['ip' => $ip]);
    }

    public function testImplementsServerIPDetectorInterface(): void
    {
        // Arrange
        $httpClient = $this->createStub(ClientInterface::class);
        $detector = new IpifyIPDetector($httpClient);

        // Assert
        $this->assertInstanceOf(ServerIPDetectorInterface::class, $detector);
    }

    public function testGetCurrentServerIPReturnsValidIPAddress(): void
    {
        // Arrange
        $httpClient = $this->createMock(ClientInterface::class);
        $httpClient->expects($this->once())
            ->method('sendRequest')
            ->willReturn(new Response(200, [], $this->getValidResponseJson()));

        $detector = new IpifyIPDetector($httpClient);

        // Act
        $ip = $detector->getCurrentServerIP();

        // Assert
        $this->assertIsString($ip);
        $this->assertEquals(self::VALID_IP, $ip);
    }

    public function testGetCurrentServerIPThrowsExceptionFor500StatusCode(): void
    {
        // Arrange
        $httpClient = $this->createMock(ClientInterface::class);
        $httpClient->expects($this->once())
            ->method('sendRequest')
            ->willReturn(new Response(500, [], '{}'));

        $detector = new IpifyIPDetector($httpClient);

        // Assert
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to retrieve server IP address. HTTP status code: 500');

        // Act
        $detector->getCurrentServerIP();
    }

    public function testGetCurrentServerIPThrowsExceptionWhenIPFieldIsMissing(): void
    {
        // Arrange
        $httpClient = $this->createMock(ClientInterface::class);
        $httpClient->expects($this->once())
            ->method('sendRequest')
            ->willReturn(new Response(200, [], json_encode(['other_field' => 'value'])));

        $detector = new IpifyIPDetector($httpClient);

        // Assert
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('The response from the IP service does not contain the expected "ip" field.');

        // Act
        $detector->getCurrentServerIP();
    }

    public function testGetCurrentServerIPThrowsExceptionForInvalidJsonResponse(): void
    {
        // Arrange
        $httpClient = $this->createMock(ClientInterface::class);
        $httpClient->expects($this->once())
            ->method('sendRequest')
            ->willReturn(new Response(200, [], 'invalid json {{{'));

        $detector = new IpifyIPDetector($httpClient);

        // Assert
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('The response from the IP service does not contain the expected "ip" field.');

        // Act
        $detector->getCurrentServerIP();
    }

    public function testGetCurrentServerIPPropagatesHttpClientException(): void
    {
        // Arrange
        $httpClient = $this->createMock(ClientInterface::class);
        $exception = new class extends \Exception implements ClientExceptionInterface {};

        $httpClient->expects($this->once())
            ->method('sendRequest')
            ->willThrowException($exception);

        $detector = new IpifyIPDetector($httpClient);

        // Assert
        $this->expectException(ClientExceptionInterface::class);

        // Act
        $detector->getCurrentServerIP();
    }
}
