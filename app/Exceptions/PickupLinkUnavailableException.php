<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a public pickup-request link has been revoked, has expired or has
 * reached its submission limit by the time the client submits the form.
 */
class PickupLinkUnavailableException extends RuntimeException {}
