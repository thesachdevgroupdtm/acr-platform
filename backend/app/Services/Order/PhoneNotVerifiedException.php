<?php

namespace App\Services\Order;

use RuntimeException;

/**
 * Phase 2.5a — user.is_verified_phone is false (D-2.5a-8).
 * Mapped to HTTP 403 by the controller.
 */
class PhoneNotVerifiedException extends RuntimeException
{
}
