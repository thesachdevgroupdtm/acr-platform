<?php

namespace App\Services\Cart;

/**
 * Phase 2.4 — unrecoverable cart merge failure.
 *
 * Thrown by CartMergeService when an invariant breaks mid-transaction
 * (e.g. a row vanishes between SELECT FOR UPDATE and UPDATE due to
 * a competing process holding a different lock window). The merge
 * transaction rolls back; controller catches and returns a 500 with
 * a generic friendly message — never a SQL trace.
 */
class CartMergeException extends \RuntimeException
{
}
