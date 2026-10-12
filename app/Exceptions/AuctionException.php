<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * A bid or auction action was refused for a reason the shopper can act on
 * (bid too low, auction closed, not the winner...). The message is safe to
 * show to the customer as-is.
 */
class AuctionException extends RuntimeException {}
