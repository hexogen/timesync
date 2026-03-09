<?php

declare(strict_types=1);

namespace Hexogen\Timesync\Tests;

use DateTimeImmutable;
use Hexogen\Timesync\Clock;
use PHPUnit\Framework\TestCase;

class ClockTest extends TestCase
{
    public function testClockImplementsClockInterface(): void
    {
        // Arrange
        $clock = new Clock(microtime(true));

        // Assert
        $this->assertInstanceOf(\Psr\Clock\ClockInterface::class, $clock);
    }

    public function testNowReturnsDateTimeImmutableWithPositiveDelta(): void
    {
        // Arrange: Create a clock with a known future timestamp
        $serverTime = microtime(true);
        $realTime = $serverTime + 5.0;
        $clock = new Clock($realTime);

        // Act
        $now = $clock->now();

        // Assert
        $this->assertInstanceOf(DateTimeImmutable::class, $now);

        // The clock should be approximately 5 seconds ahead of actual time
        $clockTimestamp = (float) $now->format('U.u');
        $actualTimestamp = microtime(true);
        $actualDelta = $clockTimestamp - $actualTimestamp;

        $this->assertGreaterThan(4.9, $actualDelta, 'Clock should be at least 4.9 seconds ahead');
        $this->assertLessThan(5.1, $actualDelta, 'Clock should be at most 5.1 seconds ahead');
    }

    public function testNowReturnsDateTimeImmutableWithNegativeDelta(): void
    {
        // Arrange: Create a clock with a past timestamp (10 seconds behind)
        $serverTime = microtime(true);
        $realTime = $serverTime - 10.0;
        $clock = new Clock($realTime);

        // Act
        $now = $clock->now();

        // Assert
        $this->assertInstanceOf(DateTimeImmutable::class, $now);

        // The clock should be approximately 10 seconds behind
        $clockTimestamp = (float) $now->format('U.u');
        $actualTimestamp = microtime(true);
        $actualDelta = $clockTimestamp - $actualTimestamp;

        $this->assertLessThan(-9.99, $actualDelta, 'Clock should be at least 9.99 seconds behind');
        $this->assertGreaterThan(-10.01, $actualDelta, 'Clock should be at most 10.01 seconds behind');
    }

    public function testNowReturnsDateTimeImmutableWithZeroDelta(): void
    {
        // Arrange: Create a clock with current timestamp
        $currentTimestamp = microtime(true);
        $clock = new Clock($currentTimestamp);

        // Act
        $now = $clock->now();

        // Assert
        $this->assertInstanceOf(DateTimeImmutable::class, $now);

        // The clock should be approximately the same as current time (within 0.01 seconds)
        $clockTimestamp = (float) $now->format('U.u');
        $actualTimestamp = microtime(true);
        $actualDelta = abs($clockTimestamp - $actualTimestamp);

        $this->assertLessThan(0.01, $actualDelta, 'Clock time should be very close to current time');
    }

    public function testNowReturnsConsistentDelta(): void
    {
        // Arrange: Create a clock with a specific delta
        $realTimestamp = microtime(true) + 100.0;
        $clock = new Clock($realTimestamp);

        // Act: Call now() multiple times with a known sleep interval
        $firstCall = $clock->now();
        usleep(100000); // Sleep for exactly 0.1 seconds
        $secondCall = $clock->now();

        // Assert: The delta between calls should match the sleep time
        $elapsed = $secondCall->format('U.u') - $firstCall->format('U.u');
        $this->assertGreaterThan(0.09, $elapsed, 'Time should have advanced by at least 0.09 seconds');
        $this->assertLessThan(0.11, $elapsed, 'Time should have advanced by at most 0.11 seconds');
    }

    public function testClockWithLargeFutureTimestamp(): void
    {
        // Arrange: Create a clock 1 hour in the future
        $serverTime = microtime(true);
        $realTime = $serverTime + 3600.0;
        $clock = new Clock($realTime);

        // Act
        $now = $clock->now();

        // Assert
        $clockTimestamp = (float) $now->format('U.u');
        $actualTimestamp = microtime(true);
        $actualDelta = $clockTimestamp - $actualTimestamp;

        $this->assertGreaterThan(3599.9, $actualDelta, 'Clock should be approximately 1 hour ahead');
        $this->assertLessThan(3600.1, $actualDelta, 'Clock should be approximately 1 hour ahead');
    }

    public function testClockWithLargePastTimestamp(): void
    {
        // Arrange: Create a clock 1 hour in the past
        $serverTime = microtime(true);
        $realTime = $serverTime - 3600.0;
        $clock = new Clock($realTime);

        // Act
        $now = $clock->now();

        // Assert
        $clockTimestamp = (float) $now->format('U.u');
        $actualTimestamp = microtime(true);
        $actualDelta = $clockTimestamp - $actualTimestamp;

        $this->assertLessThan(-3599.9, $actualDelta, 'Clock should be approximately 1 hour behind');
        $this->assertGreaterThan(-3600.1, $actualDelta, 'Clock should be approximately 1 hour behind');
    }

    public function testClockPreservesMicrosecondPrecision(): void
    {
        // Arrange: Create a clock with specific microsecond offset
        $serverTime = microtime(true);
        $realTime = $serverTime + 5.123456;
        $clock = new Clock($realTime);

        // Act
        $now = $clock->now();

        // Assert: Verify microsecond precision is preserved
        $clockTimestamp = (float) $now->format('U.u');
        $actualTimestamp = microtime(true);
        $actualDelta = $clockTimestamp - $actualTimestamp;

        // Should be very close to 5.123456 seconds
        $this->assertGreaterThan(5.12, $actualDelta, 'Clock should preserve microsecond precision');
        $this->assertLessThan(5.13, $actualDelta, 'Clock should preserve microsecond precision');
    }

    public function testMultipleCallsToNowReturnDifferentObjects(): void
    {
        // Arrange
        $clock = new Clock(microtime(true));

        // Act
        $first = $clock->now();
        $second = $clock->now();

        // Assert
        $this->assertNotSame($first, $second, 'Each call to now() should return a new DateTimeImmutable instance');
    }

    public function testClockDeltaIsPreservedAcrossMultipleCalls(): void
    {
        // Arrange: Create a clock with a known delta
        $serverTime = microtime(true);
        $delta = 42.5;
        $realTime = $serverTime + $delta;
        $clock = new Clock($realTime);

        // Act: Get the time multiple times and measure the delta
        $measurements = [];
        for ($i = 0; $i < 5; $i++) {
            $beforeMicrotime = microtime(true);
            $clockTime = $clock->now();
            $afterMicrotime = microtime(true);

            // Calculate the delta: (clockTime - actualTime)
            $clockTimestamp = (float) $clockTime->format('U.u');
            $avgMicrotime = ($beforeMicrotime + $afterMicrotime) / 2;
            $measurements[] = $clockTimestamp - $avgMicrotime;

            usleep(10000); // Small delay between measurements
        }

        // Assert: All measurements should show the same delta (within tolerance)
        $avgDelta = array_sum($measurements) / count($measurements);
        foreach ($measurements as $measurement) {
            $this->assertEqualsWithDelta($avgDelta, $measurement, 0.01, 'Delta should be consistent across calls');
        }
    }

    public function testClockWithNegativeMicroseconds(): void
    {
        // Arrange: Create a clock with negative microseconds offset
        $serverTime = microtime(true);
        $realTime = $serverTime - 2.789012;
        $clock = new Clock($realTime);

        // Act
        $now = $clock->now();

        // Assert
        $clockTimestamp = (float) $now->format('U.u');
        $actualTimestamp = microtime(true);
        $actualDelta = $clockTimestamp - $actualTimestamp;

        $this->assertLessThan(-2.78, $actualDelta, 'Clock should handle negative microsecond offsets');
        $this->assertGreaterThan(-2.80, $actualDelta, 'Clock should handle negative microsecond offsets');
    }

    public function testClockWithVerySmallDelta(): void
    {
        // Arrange: Create a clock with a very small delta (1 millisecond)
        $serverTime = microtime(true);
        $realTime = $serverTime + 0.001;
        $clock = new Clock($realTime);

        // Act
        $now = $clock->now();

        // Assert
        $clockTimestamp = (float) $now->format('U.u');
        $actualTimestamp = microtime(true);
        $actualDelta = $clockTimestamp - $actualTimestamp;

        $this->assertGreaterThan(-0.009, $actualDelta, 'Clock should handle very small deltas');
        $this->assertLessThan(0.011, $actualDelta, 'Clock should handle very small deltas');
    }

    public function testClockWithExactlyOneSecondDelta(): void
    {
        // Arrange
        $serverTime = microtime(true);
        $realTime = $serverTime + 1.0;
        $clock = new Clock($realTime);

        // Act
        $now = $clock->now();

        // Assert
        $clockTimestamp = (float) $now->format('U.u');
        $actualTimestamp = microtime(true);
        $actualDelta = $clockTimestamp - $actualTimestamp;

        $this->assertGreaterThan(0.99, $actualDelta, 'Clock should handle exactly 1 second delta');
        $this->assertLessThan(1.01, $actualDelta, 'Clock should handle exactly 1 second delta');
    }
}
