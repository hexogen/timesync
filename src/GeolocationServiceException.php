<?php

declare(strict_types=1);

namespace Hexogen\Timesync;

/**
 * Thrown when the external geolocation service returns an error response
 * or an unexpected payload.
 */
class GeolocationServiceException extends \RuntimeException implements TimesyncException {}
