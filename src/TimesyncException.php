<?php

declare(strict_types=1);

namespace Hexogen\Timesync;

/**
 * Marker interface implemented by all domain exceptions in this library.
 *
 * This allows consumers to catch a single {@see TimesyncException} type
 * for all library-specific failures, while still distinguishing between
 * concrete exception classes if needed.
 */
interface TimesyncException extends \Throwable {}
