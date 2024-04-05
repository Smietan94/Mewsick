<?php

declare(strict_types=1);

namespace App\DTO;

class SpotifyApiRequestDTO
{
    public function __construct(
        public readonly string  $method,
        public readonly string  $url,
        public readonly array   $headers,
        public readonly ?string $body  = null,
        public readonly ?array  $query = null
    ) {

    }
}