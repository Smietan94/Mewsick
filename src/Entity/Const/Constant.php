<?php

declare(strict_types=1);

namespace App\Entity\Const;

class Constant
{
    // Api public
    public const CONTENT_TYPE = 'application/x-www-form-urlencoded';
    public const SCOPE        = [
        'user-read-private',
        'user-read-email',
        'playlist-modify-public',
        'user-top-read'
    ];
    public const REDIRECT_URI = 'http://127.0.0.1:8000/';

    // Api validation
    public const ARTIST_ID_FOR_TOKEN_VALIDATION = '10wjV72OetIdsUQEcjSnOd';

    // Api uris
    public const ACCESS_TOKEN_URL = 'https://accounts.spotify.com/api/token';
    public const SPOTIFY_API_URL  = 'https://api.spotify.com/v1';
}