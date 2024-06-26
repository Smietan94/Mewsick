<?php

declare(strict_types=1);

namespace App\Entity\Const;

class Constant
{
    // Api public
    public final const CONTENT_TYPE = 'application/x-www-form-urlencoded';
    public final const SCOPE        = [
        'user-read-private',
        'user-read-email',
        'playlist-modify-public',
        'playlist-modify-private',
        'playlist-read-private',
        'playlist-read-collaborative',
        'user-top-read',
        'user-library-modify',
        'ugc-image-upload'
    ];
    public final const REDIRECT_URI = 'http://127.0.0.1:8000/';

    // Api validation
    public final const ARTIST_ID_FOR_TOKEN_VALIDATION = '10wjV72OetIdsUQEcjSnOd';

    // Api uris
    public final const AUTHORIZATION_URL = 'https://accounts.spotify.com/pl/authorize?client_id=%s&redirect_uri=%s&response_type=code&scope=%s';
    public final const ACCESS_TOKEN_URL  = 'https://accounts.spotify.com/api/token';
    public final const SPOTIFY_API_URL   = 'https://api.spotify.com/v1';

    // Prefix
    public final const SPOTIFY_PLAYLIST_PREFIX = 'Mewsick playlist by';
}