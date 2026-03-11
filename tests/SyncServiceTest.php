<?php

declare(strict_types=1);

namespace Hexogen\Timesync\Tests;

use Hexogen\Timesync\ServerIPDetectorInterface;
use Hexogen\Timesync\SyncClientInterface;
use Hexogen\Timesync\SyncService;
use Hexogen\Timesync\SyncServiceInterface;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;

class SyncServiceTest extends TestCase
{
    private const DETECTED_IP = '37.17.245.123';
    private const PROVIDED_IP = '8.8.8.8';

    public function testImplementsSyncServiceInterface(): void
    {
        // Arrange
        $syncClient = $this->createStub(SyncClientInterface::class);
        $serverIPDetector = $this->createStub(ServerIPDetectorInterface::class);

        // Act
        $service = new SyncService($syncClient, $serverIPDetector);

        // Assert
        $this->assertInstanceOf(SyncServiceInterface::class, $service);
    }

    public function testGetCurrentTimeUsesDetectorWhenIpIsNull(): void
    {
        // Arrange
        $expectedClock = $this->createStub(ClockInterface::class);

        $serverIPDetector = $this->createMock(ServerIPDetectorInterface::class);
        $serverIPDetector->expects($this->once())
            ->method('getCurrentServerIP')
            ->willReturn(self::DETECTED_IP);

        $syncClient = $this->createMock(SyncClientInterface::class);
        $syncClient->expects($this->once())
            ->method('getCurrentTime')
            ->with(self::DETECTED_IP)
            ->willReturn($expectedClock);

        $service = new SyncService($syncClient, $serverIPDetector);

        // Act
        $actualClock = $service->getCurrentTime();

        // Assert
        $this->assertSame($expectedClock, $actualClock);
    }

    public function testGetCurrentTimeUsesProvidedIpWithoutCallingDetector(): void
    {
        // Arrange
        $expectedClock = $this->createStub(ClockInterface::class);

        $serverIPDetector = $this->createMock(ServerIPDetectorInterface::class);
        $serverIPDetector->expects($this->never())
            ->method('getCurrentServerIP');

        $syncClient = $this->createMock(SyncClientInterface::class);
        $syncClient->expects($this->once())
            ->method('getCurrentTime')
            ->with(self::PROVIDED_IP)
            ->willReturn($expectedClock);

        $service = new SyncService($syncClient, $serverIPDetector);

        // Act
        $actualClock = $service->getCurrentTime(self::PROVIDED_IP);

        // Assert
        $this->assertSame($expectedClock, $actualClock);
    }

    public function testGetCurrentTimePropagatesDetectorException(): void
    {
        // Arrange
        $serverIPDetector = $this->createMock(ServerIPDetectorInterface::class);
        $serverIPDetector->expects($this->once())
            ->method('getCurrentServerIP')
            ->willThrowException(new \RuntimeException('Detector error'));

        $syncClient = $this->createMock(SyncClientInterface::class);
        $syncClient->expects($this->never())
            ->method('getCurrentTime');

        $service = new SyncService($syncClient, $serverIPDetector);

        // Assert
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Detector error');

        // Act
        $service->getCurrentTime();
    }

    public function testGetCurrentTimePropagatesSyncClientException(): void
    {
        // Arrange
        $serverIPDetector = $this->createMock(ServerIPDetectorInterface::class);
        $serverIPDetector->expects($this->never())
            ->method('getCurrentServerIP');

        $syncClient = $this->createMock(SyncClientInterface::class);
        $syncClient->expects($this->once())
            ->method('getCurrentTime')
            ->with(self::PROVIDED_IP)
            ->willThrowException(new \RuntimeException('Client error'));

        $service = new SyncService($syncClient, $serverIPDetector);

        // Assert
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Client error');

        // Act
        $service->getCurrentTime(self::PROVIDED_IP);
    }
}
