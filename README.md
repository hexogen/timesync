# Timesync

[![Package Version](https://img.shields.io/packagist/v/hexogen/timesync.svg)](https://packagist.org/packages/hexogen/timesync)
[![Tests](https://github.com/hexogen/timesync/workflows/tests/badge.svg)](https://github.com/hexogen/timesync/actions)
[![Code Coverage](https://codecov.io/gh/hexogen/timesync/branch/main/graph/badge.svg)](https://codecov.io/gh/hexogen/timesync)
[![PHP Version](https://img.shields.io/badge/php-8.3%2B-blue.svg)](https://www.php.net/releases/8.3/)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

A simple and elegant time synchronization library for PHP that allows you to synchronize your application's clock with a remote time source based on IP geolocation.

## Features

- 🕐 **PSR-20 Clock Interface** - Fully compliant with the PSR-20 Clock standard
- 🌍 **IP Geolocation** - Fetch time data for an IPv4 address via ipgeolocation.io
- 🔍 **Server IP Detection** - Automatically detect the current server IP via `SyncService`
- ⚡ **Microsecond Precision** - Maintains microsecond-level time accuracy
- 🔌 **PSR-18 HTTP Client** - Works with any PSR-18 compatible HTTP client
- 🧪 **Fully Tested** - Covered by PHPUnit tests
- 🎯 **Modern PHP** - Requires PHP 8.4+

## Installation

Install via Composer:

```bash
composer require hexogen/timesync
```

## Requirements

- PHP 8.4 or higher
- A PSR-18 compatible HTTP client (for example Guzzle or Symfony HttpClient)

## Upgrade Guide for 0.3.0

Version `0.3.0` contains a breaking API change:

- `IPGeolocationClient`, `SyncService`, and `IpifyIPDetector` now expose dedicated exception classes as part of the public contract
- Catch `InvalidIpAddressException`, `GeolocationServiceException`, and `ServerIpDetectionException` for precise failure handling
- All library-specific exceptions now implement `TimesyncException`, so you can catch a single domain-level type when preferred
- `0.2.x` already introduced the `SyncService` split for automatic server IP detection; that API remains the current usage model

### Before (`0.2.x`)

```php
use Psr\Http\Client\ClientExceptionInterface;

try {
    $clock = $syncService->getCurrentTime();
} catch (\InvalidArgumentException $e) {
    // Invalid IPv4 address format
} catch (\RuntimeException $e) {
    // API error, malformed response, or detector failure
} catch (ClientExceptionInterface $e) {
    // HTTP client error
}
```

### After (`0.3.0`)

```php
use Hexogen\Timesync\GeolocationServiceException;
use Hexogen\Timesync\InvalidIpAddressException;
use Hexogen\Timesync\ServerIpDetectionException;
use Psr\Http\Client\ClientExceptionInterface;

try {
    $clock = $syncService->getCurrentTime();
} catch (InvalidIpAddressException $e) {
    // Invalid IPv4 address format
} catch (GeolocationServiceException $e) {
    // ipgeolocation.io returned an error or malformed payload
} catch (ServerIpDetectionException $e) {
    // The current server IP could not be determined
} catch (ClientExceptionInterface $e) {
    // HTTP client transport error
}
```

You can also catch `Hexogen\Timesync\TimesyncException` to handle all library-specific failures with one catch block.

## Quick Start

```php
<?php

use GuzzleHttp\Client;
use Hexogen\Timesync\IPGeolocationClient;
use Hexogen\Timesync\IpifyIPDetector;
use Hexogen\Timesync\SyncService;

$httpClient = new Client();
$ipDetector = new IpifyIPDetector($httpClient);
$syncClient = new IPGeolocationClient(
    apiKey: 'your-ipgeolocation-api-key',
    httpClient: $httpClient,
);
$syncService = new SyncService($syncClient, $ipDetector);

$clock = $syncService->getCurrentTime();
echo $clock->now()->format('Y-m-d H:i:s.u');
```

## Usage

### Basic Clock Usage

The `Clock` class provides a synchronized time based on a reference timestamp:

```php
use Hexogen\Timesync\Clock;

$referenceTime = microtime(true) + 5.0;
$clock = new Clock($referenceTime, 'Europe/Kyiv');

$now = $clock->now();
```

### Explicit IP Lookup with `IPGeolocationClient`

Use `IPGeolocationClient` when you already know which IPv4 address you want to resolve:

```php
use GuzzleHttp\Client;
use Hexogen\Timesync\IPGeolocationClient;

$httpClient = new Client();
$client = new IPGeolocationClient('your-api-key', $httpClient);

$clock = $client->getCurrentTime('8.8.8.8');
echo $clock->now()->getTimezone()->getName();
```

### Automatic Server IP Detection with `SyncService`

Use `SyncService` when you want the library to detect the current server IP for you:

```php
use GuzzleHttp\Client;
use Hexogen\Timesync\IPGeolocationClient;
use Hexogen\Timesync\IpifyIPDetector;
use Hexogen\Timesync\SyncService;

$httpClient = new Client();
$ipDetector = new IpifyIPDetector($httpClient);
$syncClient = new IPGeolocationClient('your-api-key', $httpClient);
$syncService = new SyncService($syncClient, $ipDetector);

$clock = $syncService->getCurrentTime();
```

You can still override the detected IP explicitly:

```php
$clock = $syncService->getCurrentTime('37.17.245.123');
```

### Custom IP Detection

Implement your own IP detector:

```php
use Hexogen\Timesync\ServerIPDetectorInterface;

class CustomIPDetector implements ServerIPDetectorInterface
{
    public function getCurrentServerIP(): string
    {
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
    ClientInterface $httpClient
)

public function getCurrentTime(string $ip): ClockInterface
```

Throws `InvalidIpAddressException`, `GeolocationServiceException`, and `ClientExceptionInterface`.

### `SyncService`

Implements `SyncServiceInterface`

```php
public function __construct(
    SyncClientInterface $syncClient,
    ServerIPDetectorInterface $serverIPDetector
)

public function getCurrentTime(?string $ip = null): ClockInterface
```

Throws `InvalidIpAddressException`, `GeolocationServiceException`, `ServerIpDetectionException`, and `ClientExceptionInterface`.

### `IpifyIPDetector`

Implements `ServerIPDetectorInterface`

```php
public function __construct(ClientInterface $httpClient)

public function getCurrentServerIP(): string
```

Throws `ServerIpDetectionException` and `ClientExceptionInterface`.

### Exception Types

- `TimesyncException` — marker interface implemented by all library-specific exceptions
- `InvalidIpAddressException` — invalid IPv4 input passed to the library
- `GeolocationServiceException` — geolocation API returned an error or malformed payload
- `ServerIpDetectionException` — public server IP lookup failed or returned an invalid payload

## Configuration

### Get an ipgeolocation.io API Key

1. Sign up at [ipgeolocation.io](https://ipgeolocation.io)
2. Get your API key from the dashboard
3. Pass it to `IPGeolocationClient`

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
- **Strong typing** with explicit interfaces and return types

## Architecture

```text
┌─────────────────────────────────────┐
│   SyncService                       │
│   (SyncServiceInterface)            │
└───────────┬─────────────────────────┘
            │
            ├──► IpifyIPDetector
            │    (ServerIPDetectorInterface)
            |     └──► PSR-18 HTTP Client
            │
            └──► IPGeolocationClient
                 (SyncClientInterface)
                      │
                      ├──► PSR-18 HTTP Client
                      │
                      └──► Clock (PSR-20)
                           └──► DateTimeImmutable
```

## How It Works

1. **Optional IP Detection**: `SyncService` fetches the current server IP when you do not provide one
2. **Geolocation**: `IPGeolocationClient` queries the ipgeolocation.io API with an IPv4 address
3. **Time Extraction**: The API returns the current time and timezone for that IP's location
4. **Clock Creation**: A `Clock` instance stores the time delta and timezone
5. **Synchronized Time**: The clock provides synchronized time adjusted for the delta

## Error Handling

All library-specific exceptions implement `Hexogen\Timesync\TimesyncException`.

```php
use Hexogen\Timesync\GeolocationServiceException;
use Hexogen\Timesync\InvalidIpAddressException;
use Hexogen\Timesync\ServerIpDetectionException;
use Hexogen\Timesync\TimesyncException;
use Psr\Http\Client\ClientExceptionInterface;

try {
    $clock = $syncService->getCurrentTime();
} catch (InvalidIpAddressException $e) {
    // Invalid IPv4 address format supplied to the library
    echo $e->getMessage();
} catch (GeolocationServiceException $e) {
    // ipgeolocation.io returned an error or unexpected payload
    echo $e->getMessage();
} catch (ServerIpDetectionException $e) {
    // The current server IP could not be determined
    echo $e->getMessage();
} catch (ClientExceptionInterface $e) {
    // HTTP client transport error
    echo $e->getMessage();
} catch (TimesyncException $e) {
    // Optional single catch for any other library-specific exception type
    echo $e->getMessage();
}
```

## Performance

- **Delta calculation**: The clock calculates the time offset once per instance
- **Lightweight**: Minimal overhead, no background processes
- **Composable**: Cache `Clock` or `SyncService` results in your DI container if needed

## Examples

### Laravel Integration

```php
// In a service provider
$this->app->singleton(ClockInterface::class, function () {
    $httpClient = new \GuzzleHttp\Client();
    $ipDetector = new IpifyIPDetector($httpClient);
    $syncClient = new IPGeolocationClient(
        config('services.ipgeolocation.key'),
        $httpClient,
    );
    $syncService = new SyncService($syncClient, $ipDetector);

    return $syncService->getCurrentTime();
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

    Hexogen\Timesync\SyncService:
        arguments:
            - '@Hexogen\Timesync\IPGeolocationClient'
            - '@Hexogen\Timesync\IpifyIPDetector'
```

## Contributing

Contributions are welcome. Feel free to submit a Pull Request.

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

This library is licensed under the MIT License. See [LICENSE](LICENSE) for details.

## Credits

- Developed by [Hexogen](https://github.com/hexogen)
- Uses [ipgeolocation.io](https://ipgeolocation.io) for time synchronization
- Uses [ipify.org](https://www.ipify.org) for server IP detection

## Changelog

### 0.3.0 (2026-03-11)

- **Breaking**: introduced dedicated domain exceptions as part of the public API: `InvalidIpAddressException`, `GeolocationServiceException`, and `ServerIpDetectionException`
- **Breaking**: added `TimesyncException` as a marker interface for all library-specific exceptions
- Updated interface contracts and README examples to document the new exception handling model
- Corrected the README PHP requirement to match `composer.json` (`PHP 8.3+`)

### 0.2.0 (2026-03-11)

- **Breaking**: moved optional server IP detection from `IPGeolocationClient` to `SyncService`
- **Breaking**: `IPGeolocationClient::__construct()` now accepts only `apiKey` and `httpClient`
- **Breaking**: `IPGeolocationClient::getCurrentTime()` now requires an explicit IPv4 string
- Updated documentation and examples to reflect the new API split

### 0.1.1 (2026-03-10)

- Added MIT `LICENSE` file
- Improved workflow with coverage reporting
- README fixes and consistency improvements

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

