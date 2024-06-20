<?php

declare(strict_types=1);

namespace App\DTO;

class SpotifyPlaylistDTO
{
    public function __construct(
        public readonly string $catName,
        public readonly string $name,
        public readonly string $description,
        public readonly string $imgUrl,
        public readonly string $uri
    ) {
    }
}