<?php

namespace App\Services\Order;

use RuntimeException;

/**
 * Phase 2.5a — same phone + same primary service + same slot within
 * 30 minutes (D-2.5a-8). Mapped to HTTP 422 by the controller.
 */
class DuplicateBookingException extends RuntimeException
{
}
