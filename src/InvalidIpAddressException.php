<?php

declare(strict_types=1);

namespace Hexogen\Timesync;

/**
 * Thrown when an invalid IP address is provided to the library.
 */
class InvalidIpAddressException extends \InvalidArgumentException implements TimesyncException {}
