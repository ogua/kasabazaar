<?php

namespace App\Services\Whatsapp;

use RuntimeException;

class WhatsappGatewayException extends RuntimeException
{
    public function __construct(string $message, public readonly ?int $status = null)
    {
        parent::__construct($message);
    }
}
