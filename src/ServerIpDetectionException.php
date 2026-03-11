<?php

declare(strict_types=1);

namespace Hexogen\Timesync;

/**
 * Thrown when the library is unable to determine the current server IP
 * due to an HTTP error or an unexpected response payload.
 */
class ServerIpDetectionException extends \RuntimeException implements TimesyncException {}
