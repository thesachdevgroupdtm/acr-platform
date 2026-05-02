<?php

namespace App\Services\Cart;

/**
 * Thrown by CartService::priceServiceItem when neither a vehicle-
 * specific service_prices row nor a service base_price exists.
 *
 * CartController catches this and returns a 422 to the client per
 * /PHASE2_CONTRACT.md §6.6 — the cart never silently picks a wrong
 * price.
 */
class NoPriceConfiguredException extends \DomainException
{
}
