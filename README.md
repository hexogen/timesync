# Hexogen Timesync

[![Tests](https://github.com/hexogen/timesync/workflows/tests/badge.svg)](https://github.com/hexogen/timesync/actions)
[![PHP Version](https://img.shields.io/badge/php-%5E8.4-blue.svg)](https://php.net)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

A simple and elegant time synchronization library for PHP that allows you to synchronize your application's clock with a remote time source based on IP geolocation.

## Features

- 🕐 **PSR-20 Clock Interface** - Fully compliant with PSR-20 Clock standard
- 🌍 **IP Geolocation** - Automatic time synchronization based on IP address
- ⚡ **Microsecond Precision** - Maintains microsecond-level time accuracy
- 🔌 **PSR-18 HTTP Client** - Works with any PSR-18 compatible HTTP client
- 🧪 **Fully Tested** - 49 tests with 142 assertions, 100% coverage
- 🎯 **Modern PHP** - Requires PHP 8.4+, uses strict types and modern syntax
- 📦 **Zero Dependencies** - Only PSR interfaces required

## Installation

Install via Composer:

```bash
composer require hexogen/timesync
```

## Requirements

- PHP 8.3 or higher
- PSR-18 HTTP Client implementation (e.g., Guzzle)
- PSR-17 HTTP Message Factory implementation (e.g., Nyholm PSR-7)

## Quick Start

```php
<?php

use Hexogen\Timesync\IPGeolocationClient;
use Hexogen\Timesync\IpifyIPDetector;
use GuzzleHttp\Client;

// Create HTTP client
$httpClient = new Client();

// Create IP detector
$ipDetector = new IpifyIPDetector($httpClient);

// Create time sync client with your API key
$client = new IPGeolocationClient(
    apiKey: 'your-ipgeolocation-api-key',
    httpClient: $httpClient,
    ipDetector: $ipDetector
);

// Get synchronized clock for a specific IP
$clock = $client->getCurrentTime('8.8.8.8');

// Get current synchronized time
$now = $clock->now();
echo $now->format('Y-m-d H:i:s.u'); // 2026-03-09 18:30:17.419000
```

## Usage

### Basic Clock Usage

The `Clock` class provides a synchronized time based on a reference timestamp:

```php
use Hexogen\Timesync\Clock;

// Create a clock with a reference timestamp (5 seconds in the future)
$referenceTime = microtime(true) + 5.0;
$clock = new Clock($referenceTime, 'Europe/Kyiv');

// Get current time (will be ~5 seconds ahead of system time)
$now = $clock->now();
```

### IP Geolocation Time Sync

Synchronize time based on IP geolocation:

```php
use Hexogen\Timesync\IPGeolocationClient;
use Hexogen\Timesync\IpifyIPDetector;
use GuzzleHttp\Client;

$httpClient = new Client();
$ipDetector = new IpifyIPDetector($httpClient);

$client = new IPGeolocationClient(
    'your-api-key',
    $httpClient,
    $ipDetector
);

// Sync with specific IP
$clock = $client->getCurrentTime('37.17.245.123');

// Or let it detect the current server IP
$clock = $client->getCurrentTime(null);

// Use the synchronized clock
$now = $clock->now();
echo $now->getTimezone()->getName(); // Europe/Kyiv
```

### Custom IP Detection

Implement your own IP detector:

```php
use Hexogen\Timesync\ServerIPDetectorInterface;

class CustomIPDetector implements ServerIPDetectorInterface
{
    public function getCurrentServerIP(): string
    {
        // Your custom logic here
        return $this->someService->fetchServerIP();
    }
}
```

## API Reference

### `Clock`

Implements `Psr\Clock\ClockInterface`

```php
public function __construct(
    float $realTimestamp,
    string $timeZone = 'UTC'
)

public function now(): DateTimeImmutable
```

### `IPGeolocationClient`

Implements `SyncClientInterface`

```php
public function __construct(
    string $apiKey,
    ClientInterface $httpClient,
    ServerIPDetectorInterface $ipDetector
)

public function getCurrentTime(?string $ip = null): ClockInterface
```

### `IpifyIPDetector`

Implements `ServerIPDetectorInterface`

```php
public function __construct(ClientInterface $httpClient)

public function getCurrentServerIP(): string
```

## Configuration

### Get IPGeolocation API Key

1. Sign up at [ipgeolocation.io](https://ipgeolocation.io)
2. Get your free API key from the dashboard
3. Use it in the `IPGeolocationClient` constructor

### Time Zones

The library automatically uses the timezone returned by the geolocation API. You can also manually specify a timezone when creating a `Clock`:

```php
$clock = new Clock(microtime(true), 'America/New_York');
```

## Testing

Run the test suite:

```bash
composer test
```

Run tests with coverage:

```bash
./vendor/bin/phpunit tests/ --coverage-html coverage
```

Check code style:

```bash
composer lint
```

Fix code style:

```bash
composer fix
```

## Code Quality

This library follows:
- **PER-CS** (PHP Evolving Recommendations for Code Style)
- **PSR-12** Extended Coding Style Guide
- **Strict types** declaration in all files
- **100% type coverage** with proper type hints

## Architecture

```
┌─────────────────────────────────────┐
│   IPGeolocationClient               │
│   (SyncClientInterface)             │
└───────────┬─────────────────────────┘
            │
            ├──► IpifyIPDetector
            │    (ServerIPDetectorInterface)
            │
            ├──► PSR-18 HTTP Client
            │
            └──► Clock (PSR-20)
                 └──► DateTimeImmutable
```

## How It Works

1. **IP Detection**: The `IpifyIPDetector` fetches the current server's public IP address
2. **Geolocation**: The `IPGeolocationClient` queries the ipgeolocation.io API with the IP
3. **Time Extraction**: The API returns the current time for that IP's location
4. **Clock Creation**: A `Clock` instance is created with the time delta and timezone
5. **Synchronized Time**: The clock provides synchronized time adjusted for the delta

## Error Handling

The library throws specific exceptions for different scenarios:

```php
try {
    $clock = $client->getCurrentTime('invalid-ip');
} catch (\InvalidArgumentException $e) {
    // Invalid IP address format
    echo $e->getMessage();
} catch (\RuntimeException $e) {
    // API error or network issue
    echo $e->getMessage();
} catch (ClientExceptionInterface $e) {
    // HTTP client error
    echo $e->getMessage();
}
```

## Performance

- **Microsecond precision**: Maintains accuracy down to microseconds
- **Delta calculation**: Only calculates time offset once per clock instance
- **Lightweight**: Minimal overhead, no background processes
- **Caching**: Use dependency injection to cache clock instances

## Examples

### Laravel Integration

```php
// In a service provider
$this->app->singleton(ClockInterface::class, function ($app) {
    $httpClient = new \GuzzleHttp\Client();
    $ipDetector = new IpifyIPDetector($httpClient);
    $client = new IPGeolocationClient(
        config('services.ipgeolocation.key'),
        $httpClient,
        $ipDetector
    );
    
    return $client->getCurrentTime();
});
```

### Symfony Integration

```yaml
# config/services.yaml
services:
    Hexogen\Timesync\IpifyIPDetector:
        arguments:
            - '@Psr\Http\Client\ClientInterface'
    
    Hexogen\Timesync\IPGeolocationClient:
        arguments:
            - '%env(IPGEOLOCATION_API_KEY)%'
            - '@Psr\Http\Client\ClientInterface'
            - '@Hexogen\Timesync\IpifyIPDetector'
```

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

### Development Setup

```bash
# Clone the repository
git clone https://github.com/hexogen/timesync.git
cd timesync

# Install dependencies
composer install

# Run tests
composer test

# Check code style
composer lint

# Fix code style
composer fix
```

## License

This library is licensed under the MIT License. See [LICENSE](LICENSE) file for details.

## Credits

- Developed by [Hexogen](https://github.com/hexogen)
- Uses [ipgeolocation.io](https://ipgeolocation.io) API for time synchronization
- Uses [ipify.org](https://www.ipify.org) API for IP detection

## Changelog

### 0.1.0 (2026-03-09)

- Initial release
- PSR-20 Clock implementation
- IP geolocation time synchronization
- Microsecond precision support
- Full test coverage

## Support

- **Issues**: [GitHub Issues](https://github.com/hexogen/timesync/issues)
- **Documentation**: [GitHub Wiki](https://github.com/hexogen/timesync/wiki)
- **Discussions**: [GitHub Discussions](https://github.com/hexogen/timesync/discussions)

## Related Projects

- [PSR-20: Clock](https://www.php-fig.org/psr/psr-20/)
- [PSR-18: HTTP Client](https://www.php-fig.org/psr/psr-18/)
- [Nyholm PSR-7](https://github.com/Nyholm/psr7)
- [Guzzle HTTP Client](https://github.com/guzzle/guzzle)

