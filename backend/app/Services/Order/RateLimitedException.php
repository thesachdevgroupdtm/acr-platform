<?php

namespace App\Services\Order;

use RuntimeException;

/**
 * Phase 2.5a — phone-based order rate-limit hit (3/hr or 5/day).
 * Mapped to HTTP 429 by the controller.
 */
class RateLimitedException extends RuntimeException
{
}
