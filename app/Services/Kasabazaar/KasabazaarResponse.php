<?php

namespace App\Services\Kasabazaar;

readonly class KasabazaarResponse
{
    public function __construct(
        public bool $success,
        public mixed $data,
        public ?string $message = null,
        public ?array $meta = null,
    ) {}
}
